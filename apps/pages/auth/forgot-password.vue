<template>
  <view class="page">
    <view class="notice-box">
      <view class="notice-icon">!</view>
      <view class="notice-title">找回密码</view>
      <view class="notice-text">
        MVP 阶段暂未开放此功能，请联系管理员重置密码。
      </view>
    </view>

    <button class="btn btn-primary" @click="goBack">返回登录</button>
  </view>
</template>

<script>
export default {
  methods: {
    goBack() {
      uni.navigateBack()
    },
  },
}
</script>

<style>
@import '@/common/auth.css';

.page {
  padding: 80rpx 40rpx;
  min-height: 100vh;
  background: #f5f5f5;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.notice-box {
  background: #fff;
  border-radius: 16rpx;
  padding: 60rpx 40rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 60rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.notice-icon {
  width: 100rpx;
  height: 100rpx;
  border-radius: 50rpx;
  background: #fff3e0;
  color: #ff9800;
  font-size: 60rpx;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32rpx;
}

.notice-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 20rpx;
}

.notice-text {
  font-size: 28rpx;
  color: #999;
  text-align: center;
  line-height: 1.6;
}

.btn-primary {
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  background: #007aff;
  border-radius: 44rpx;
  color: #fff;
  font-size: 32rpx;
}
</style>
