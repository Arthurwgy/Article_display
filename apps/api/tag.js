import { get } from '@/utils/request.js'

export const tagApi = {
  list: (q) => get('/tags', { params: { q } }),
}
