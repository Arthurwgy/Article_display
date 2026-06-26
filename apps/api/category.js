import { get } from '@/utils/request.js'

export const categoryApi = {
  list: () => get('/categories'),
}
