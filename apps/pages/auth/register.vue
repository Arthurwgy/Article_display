<template>
  <view class="page">
    <view class="form-title">注册</view>

    <view class="form-group">
      <input
        v-model="form.name"
        class="form-input"
        placeholder="昵称（2-50字）"
        maxlength="50"
      />
    </view>

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
        placeholder="密码（至少8位）"
      />
    </view>

    <view class="form-group">
      <input
        v-model="form.password_confirmation"
        class="form-input"
        password
        placeholder="确认密码"
      />
    </view>

    <view v-if="errorMsg" class="error-tip">{{ errorMsg }}</view>

    <button
      class="btn btn-primary"
      :loading="loading"
      @click="handleRegister"
    >
      注册
    </button>

    <view class="form-footer">
      <text class="link" @click="goToLogin">已有账号？去登录</text>
    </view>
  </view>
</template>

<script>
import { useUserStore } from '@/store/modules/user.js'

export default {
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
      },
      loading: false,
      errorMsg: '',
    }
  },
  methods: {
    async handleRegister() {
      if (!this.form.name) {
        this.errorMsg = '请输入昵称'
        return
      }
      if (!this.form.email) {
        this.errorMsg = '请输入邮箱'
        return
      }
      if (!this.form.password) {
        this.errorMsg = '请输入密码'
        return
      }
      if (this.form.password !== this.form.password_confirmation) {
        this.errorMsg = '两次密码输入不一致'
        return
      }
      if (this.form.password.length < 8) {
        this.errorMsg = '密码至少8位'
        return
      }

      this.errorMsg = ''
      this.loading = true

      try {
        const userStore = useUserStore()
        await userStore.register(this.form)
        uni.reLaunch({ url: '/pages/mine/mine' })
      } catch (err) {
        if (err && err.errors) {
          const firstField = Object.keys(err.errors)[0]
          this.errorMsg = err.errors[firstField][0]
        } else if (err && err.message) {
          this.errorMsg = err.message
        } else {
          this.errorMsg = '注册失败'
        }
      } finally {
        this.loading = false
      }
    },
    goToLogin() {
      uni.navigateBack()
    },
  },
}
</script>

<style>
@import '@/common/auth.css';
</style>
