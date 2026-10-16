<?php

namespace App\Services\Issues;

use App\Enums\IssueLinkType;
use App\Events\IssueLinkCreated;
use App\Events\IssueLinkDeleted;
use App\Models\Issue;
use App\Models\IssueLink;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Directed/symmetric relationships between issues.
 */
final class IssueLinkService
{
    public function __construct(
        private readonly ActivityLogService $activityLogs,
    ) {
    }

    /**
     * @return Collection<int, IssueLink>
     */
    public function listForIssue(Issue $issue): Collection
    {
        return IssueLink::query()
            ->where(function ($query) use ($issue) {
                $query->where('source_issue_id', $issue->id)
                    ->orWhere('target_issue_id', $issue->id);
            })
            ->with(['sourceIssue', 'targetIssue', 'creator'])
            ->orderBy('created_at')
            ->get();
    }

    public function create(Issue $source, User $actor, int $targetId, IssueLinkType $type): IssueLink
    {
        if ($source->id === $targetId) {
            throw ValidationException::withMessages([
                'target_issue_id' => 'An issue cannot be linked to itself.',
            ]);
        }

        /** @var Issue|null $target */
        $target = Issue::find($targetId);

        if ($target === null) {
            throw ValidationException::withMessages([
                'target_issue_id' => 'The target issue does not exist.',
            ]);
        }

        if ($target->project_id !== $source->project_id) {
            throw ValidationException::withMessages([
                'target_issue_id' => 'Linked issues must belong to the same project.',
            ]);
        }

        // Symmetric relationships are stored once using a canonical ordering so
        // (A relates_to B) and (B relates_to A) collapse to a single row.
        if ($type->isSymmetric() && $targetId < $source->id) {
            $src = $target;
            $tgt = $source;
        } else {
            $src = $source;
            $tgt = $target;
        }

        $exists = IssueLink::query()
            ->where('source_issue_id', $src->id)
            ->where('target_issue_id', $tgt->id)
            ->where('link_type', $type->value)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'target_issue_id' => 'These issues are already linked with this type.',
            ]);
        }

        if ($type === IssueLinkType::BLOCKS && $this->wouldCreateCycle($src->id, $tgt->id)) {
            throw ValidationException::withMessages([
                'target_issue_id' => 'This link would create a circular blocking relationship.',
            ]);
        }

        $link = DB::transaction(function () use ($source, $target, $actor, $type, $src, $tgt) {
            $link = IssueLink::create([
                'source_issue_id' => $src->id,
                'target_issue_id' => $tgt->id,
                'link_type' => $type->value,
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->activityLogs->logIssueLinked($source, $actor, $type, $target);

            return $link;
        });

        DB::afterCommit(function () use ($link) {
            event(new IssueLinkCreated($link));
        });

        return $link->load(['sourceIssue', 'targetIssue', 'creator']);
    }

    public function delete(IssueLink $link, User $actor): void
    {
        $sourceId = $link->source_issue_id;
        $targetId = $link->target_issue_id;
        $linkId = $link->id;
        $type = $link->link_type;
        $typeLabel = $type instanceof IssueLinkType ? $type->label() : (string) $type;
        $source = $link->sourceIssue;

        DB::transaction(function () use ($link, $source, $actor, $typeLabel, $targetId) {
            if ($source !== null) {
                $this->activityLogs->logIssueLinkRemoved($source, $actor, $typeLabel, $targetId);
            }

            $link->delete();
        });

        DB::afterCommit(function () use ($sourceId, $targetId, $linkId) {
            event(new IssueLinkDeleted($sourceId, $targetId, $linkId));
        });
    }

    /**
     * Detect whether adding "source blocks target" would close a cycle by
     * walking the existing outgoing BLOCKS edges from the target.
     */
    private function wouldCreateCycle(int $sourceId, int $targetId): bool
    {
        $visited = [];
        $stack = [$targetId];

        while ($stack !== []) {
            $current = array_pop($stack);

            if ($current === $sourceId) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            $next = IssueLink::query()
                ->where('source_issue_id', $current)
                ->where('link_type', IssueLinkType::BLOCKS->value)
                ->pluck('target_issue_id')
                ->all();

            foreach ($next as $id) {
                $stack[] = (int) $id;
            }
        }

        return false;
    }
}
