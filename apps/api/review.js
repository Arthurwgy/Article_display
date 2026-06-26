import { get, post } from '@/utils/request.js'

export const reviewApi = {
  logs: (articleId) => get(`/articles/${articleId}/review-logs`),
  appeal: (articleId, reason) => post(`/articles/${articleId}/appeal`, { reason }),
  resubmit: (articleId) => post(`/articles/${articleId}/resubmit`),
}
