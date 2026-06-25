import { defineStore } from 'pinia'
import { getToken, setToken, removeToken } from '@/utils/auth.js'
import { authApi } from '@/api/auth.js'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: getToken() || '',
    userInfo: null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token && !!state.userInfo,
  },

  actions: {
    clear() {
      this.token = ''
      this.userInfo = null
      removeToken()
    },

    async login(formData) {
      const res = await authApi.login(formData)
      this.token = res.access_token
      this.userInfo = res.user
      setToken(res.access_token)
    },

    async register(formData) {
      const res = await authApi.register(formData)
      this.token = res.access_token
      this.userInfo = res.user
      setToken(res.access_token)
    },

    async logout() {
      try {
        await authApi.logout()
      } catch (e) {
        // 忽略 logout 接口失败，继续清状态
      }
      this.clear()
      uni.reLaunch({ url: '/pages/auth/login' })
    },

    async fetchMe() {
      const res = await authApi.me()
      this.userInfo = res.user
    },
  },
})
