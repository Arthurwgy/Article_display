<template>
  <view class="timeline">
    <view
      v-for="(item, index) in items"
      :key="item.id"
      class="timeline-item"
      :class="{ 'last': index === items.length - 1 }"
    >
      <view class="timeline-left">
        <view class="timeline-dot" :class="dotClass(item.action)"></view>
        <view v-if="index < items.length - 1" class="timeline-line"></view>
      </view>
      <view class="timeline-content">
        <view class="timeline-header">
          <text class="timeline-action">{{ actionLabel(item.action) }}</text>
          <text class="timeline-time">{{ formatTime(item.created_at) }}</text>
        </view>
        <view class="timeline-body">
          <view v-if="item.reviewer" class="timeline-reviewer">
            <text>审核人：{{ item.reviewer.name }}</text>
          </view>
          <view v-if="item.reason" class="timeline-reason">
            <text>{{ item.reason }}</text>
          </view>
          <view v-if="item.snapshot" class="timeline-snapshot">
            <text class="snapshot-label">修改要求：</text>
            <text class="snapshot-content">{{ item.snapshot }}</text>
          </view>
        </view>
      </view>
    </view>
    <view v-if="items.length === 0" class="timeline-empty">暂无审核记录</view>
  </view>
</template>

<script>
const ACTION_LABELS = {
  auto_reject: '机审驳回',
  submit: '提交审核',
  first_pass: '初审通过',
  first_reject: '初审驳回',
  modify_required: '要求修改',
  appeal: '申诉提交',
  second_pass: '二审通过',
  second_reject: '二审驳回',
}

const ACTION_DOTS = {
  auto_reject: 'dot-error',
  submit: 'dot-pending',
  first_pass: 'dot-success',
  first_reject: 'dot-error',
  modify_required: 'dot-warn',
  appeal: 'dot-info',
  second_pass: 'dot-success',
  second_reject: 'dot-error',
}

export default {
  name: 'AppTimeline',
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  methods: {
    actionLabel(action) {
      return ACTION_LABELS[action] || action
    },
    dotClass(action) {
      return ACTION_DOTS[action] || 'dot-default'
    },
    formatTime(timeStr) {
      if (!timeStr) return ''
      const d = new Date(timeStr)
      const pad = (n) => String(n).padStart(2, '0')
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
    },
  },
}
</script>

<style scoped>
.timeline {
  padding: 16rpx 0;
}

.timeline-item {
  display: flex;
  gap: 20rpx;
}

.timeline-left {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  width: 40rpx;
}

.timeline-dot {
  width: 20rpx;
  height: 20rpx;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 8rpx;
}

.timeline-line {
  width: 4rpx;
  flex: 1;
  min-height: 40rpx;
  background: #e8e8e8;
  margin-top: 8rpx;
}

.timeline-item.last .timeline-line {
  display: none;
}

.timeline-content {
  flex: 1;
  padding-bottom: 32rpx;
}

.timeline-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8rpx;
}

.timeline-action {
  font-size: 28rpx;
  font-weight: 600;
  color: #333;
}

.timeline-time {
  font-size: 24rpx;
  color: #999;
}

.timeline-body {
  font-size: 26rpx;
  color: #666;
  line-height: 1.6;
}

.timeline-reviewer {
  color: #999;
  font-size: 24rpx;
  margin-bottom: 4rpx;
}

.timeline-reason {
  color: #e8684a;
  background: #fff2f0;
  border-left: 4rpx solid #ff4d4f;
  padding: 8rpx 16rpx;
  border-radius: 0 6rpx 6rpx 0;
  margin-top: 6rpx;
}

.timeline-snapshot {
  background: #fff7e6;
  border-left: 4rpx solid #fa8c16;
  padding: 8rpx 16rpx;
  border-radius: 0 6rpx 6rpx 0;
  margin-top: 6rpx;
}

.snapshot-label {
  font-weight: 600;
  color: #fa8c16;
}

.snapshot-content {
  color: #8c5a00;
}

.timeline-empty {
  text-align: center;
  color: #999;
  font-size: 26rpx;
  padding: 40rpx 0;
}

/* dot variants */
.dot-default { background: #d9d9d9; }
.dot-pending { background: #faad14; }
.dot-success { background: #52c41a; }
.dot-error { background: #ff4d4f; }
.dot-warn { background: #fa8c16; }
.dot-info { background: #1890ff; }
</style>
