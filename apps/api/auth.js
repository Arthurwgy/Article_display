import { get, post } from '@/utils/request.js'

export const authApi = {
  register: (data) => post('/auth/register', data),
  login: (data) => post('/auth/login', data),
  logout: () => post('/auth/logout'),
  me: () => get('/auth/me'),
}
