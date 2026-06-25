<template>
  <view class="page">
    <view class="welcome">欢迎来到文章发布平台</view>
    <view class="hint">登录后可发布文章、参与社区互动</view>
  </view>
</template>

<script>
export default {
  data() {
    return {}
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

.welcome {
  font-size: 48rpx;
  font-weight: bold;
  color: #333;
  margin-bottom: 24rpx;
  margin-top: 200rpx;
  text-align: center;
}

.hint {
  font-size: 28rpx;
  color: #999;
  text-align: center;
}
</style>
