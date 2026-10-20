<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AvatarOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeAvatars extends Command
{
    protected $signature = 'avatars:optimize';

    protected $description = 'Re-encode stored avatars (resize + WebP) and drop the originals';

    public function handle(AvatarOptimizer $optimizer): int
    {
        $disk = Storage::disk('public');
        $optimized = 0;
        $skipped = 0;

        User::query()->whereNotNull('avatar')->each(function (User $user) use ($optimizer, $disk, &$optimized, &$skipped) {
            $original = $user->avatar;

            // Skipped when missing, external, already WebP, or undecodable.
            $newPath = $optimizer->optimizeStored($original);

            if ($newPath === null || $newPath === $original) {
                $skipped++;

                return;
            }

            $user->forceFill(['avatar' => $newPath])->save();

            if ($disk->exists($original)) {
                $disk->delete($original);
            }

            $optimized++;
        });

        $this->info("Optimized {$optimized} avatar(s); skipped {$skipped}.");

        return self::SUCCESS;
    }
}
