import echo from '../echo'

export function listenToProjectIssues(projectId, callback) {
  return echo.private(`project.${projectId}`).listen('.issue.created', callback)
}
