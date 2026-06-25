<template>
  <view class="page">
    <view class="form-title">登录</view>

    <view class="form-group">
      <input
        v-model="form.email"
        class="form-input"
        type="email"
        placeholder="邮箱"
      />
    </view>

    <view class="form-group">
      <input
        v-model="form.password"
        class="form-input"
        password
        placeholder="密码"
      />
    </view>

    <view v-if="errorMsg" class="error-tip">{{ errorMsg }}</view>

    <button
      class="btn btn-primary"
      :loading="loading"
      @click="handleLogin"
    >
      登录
    </button>

    <view class="form-footer">
      <text class="link" @click="goToRegister">还没有账号？去注册</text>
    </view>
  </view>
</template>

<script>
import { useUserStore } from '@/store/modules/user.js'

export default {
  data() {
    return {
      form: {
        email: '',
        password: '',
      },
      loading: false,
      errorMsg: '',
    }
  },
  methods: {
    async handleLogin() {
      if (!this.form.email) {
        this.errorMsg = '请输入邮箱'
        return
      }
      if (!this.form.password) {
        this.errorMsg = '请输入密码'
        return
      }

      this.errorMsg = ''
      this.loading = true

      try {
        const userStore = useUserStore()
        await userStore.login(this.form)
        uni.reLaunch({ url: '/pages/mine/mine' })
      } catch (err) {
        if (err && err.errors) {
          const firstField = Object.keys(err.errors)[0]
          this.errorMsg = err.errors[firstField][0]
        } else {
          this.errorMsg = (err && err.message) || '登录失败'
        }
      } finally {
        this.loading = false
      }
    },
    goToRegister() {
      uni.navigateTo({ url: '/pages/auth/register' })
    },
  },
}
</script>

<style>
@import '@/common/auth.css';
</style>
