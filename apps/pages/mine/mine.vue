<template>
  <view class="page">
    <view v-if="isLoggedIn" class="user-card">
      <view
        class="avatar"
        :style="{ background: avatarColor }"
      >
        <text class="avatar-text">{{ avatarInitial }}</text>
      </view>
      <view class="info">
        <text class="nickname">{{ userInfo.name }}</text>
        <text class="role">{{ roleLabel }}</text>
      </view>
    </view>

    <view v-if="isLoggedIn" class="balance-section">
      <view class="balance-item">
        <text class="balance-label">硬币</text>
        <text class="balance-value">{{ userInfo.coin_balance }}</text>
      </view>
      <view class="balance-divider" />
      <view class="balance-item">
        <text class="balance-label">金币</text>
        <text class="balance-value">{{ userInfo.gold_balance }}</text>
      </view>
    </view>

    <view v-if="isLoggedIn" class="actions">
      <button class="btn btn-outline" @click="handleLogout">退出登录</button>
    </view>
  </view>
</template>

<script>
import { useUserStore } from '@/store/modules/user.js'

export default {
  data() {
    return {}
  },
  computed: {
    userStore() {
      return useUserStore()
    },
    isLoggedIn() {
      return this.userStore.isLoggedIn
    },
    userInfo() {
      return this.userStore.userInfo
    },
    roleLabel() {
      const map = { reader: '读者', author: '作者', admin: '管理员' }
      return map[this.userInfo?.role] || '读者'
    },
    avatarInitial() {
      return (this.userInfo?.name || '?')[0].toUpperCase()
    },
    avatarColor() {
      const colors = ['#5B8FF9', '#5AD8A6', '#F6BD16', '#E8684A', '#6DC8EC', '#9270CA']
      const id = this.userInfo?.id || 'x'
      return colors[id.charCodeAt(0) % colors.length]
    },
  },
  onShow() {
    if (!this.userStore.token) {
      uni.reLaunch({ url: '/pages/auth/login' })
      return
    }
    if (!this.userStore.userInfo) {
      this.userStore.fetchMe()
    }
  },
  methods: {
    async handleLogout() {
      await this.userStore.logout()
    },
  },
}
</script>

<style>
@import '@/common/auth.css';

.page {
  padding: 40rpx;
  min-height: 100vh;
  background: #f5f5f5;
}

.user-card {
  display: flex;
  align-items: center;
  background: #fff;
  border-radius: 16rpx;
  padding: 40rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.avatar {
  width: 120rpx;
  height: 120rpx;
  border-radius: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-text {
  font-size: 48rpx;
  font-weight: bold;
  color: #fff;
}

.info {
  margin-left: 32rpx;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.nickname {
  font-size: 36rpx;
  font-weight: 600;
  color: #333;
}

.role {
  font-size: 24rpx;
  color: #999;
}

.balance-section {
  display: flex;
  align-items: center;
  background: #fff;
  border-radius: 16rpx;
  padding: 32rpx 40rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.balance-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
}

.balance-label {
  font-size: 24rpx;
  color: #999;
}

.balance-value {
  font-size: 40rpx;
  font-weight: bold;
  color: #333;
}

.balance-divider {
  width: 1rpx;
  height: 60rpx;
  background: #eee;
}

.actions {
  margin-top: 40rpx;
}

.btn-outline {
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  background: #fff;
  border: 2rpx solid #007aff;
  border-radius: 44rpx;
  color: #007aff;
  font-size: 32rpx;
}
</style>
