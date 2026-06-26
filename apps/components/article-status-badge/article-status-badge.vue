<template>
  <view class="status-badge" :class="'badge-' + status">
    {{ label }}
  </view>
</template>

<script>
const STATUS_MAP = {
  draft: { label: '草稿', class: 'draft' },
  pending: { label: '初审中', class: 'pending' },
  first_pass: { label: '初审通过', class: 'first-pass' },
  published: { label: '已发布', class: 'published' },
  first_reject: { label: '初审驳回', class: 'first-reject' },
  modify_required: { label: '要求修改', class: 'modify-required' },
  appealing: { label: '申诉中', class: 'appealing' },
  second_pass: { label: '二审通过', class: 'second-pass' },
  second_reject: { label: '最终驳回', class: 'second-reject' },
}

export default {
  name: 'ArticleStatusBadge',
  props: {
    status: {
      type: String,
      required: true,
    },
  },
  computed: {
    label() {
      return STATUS_MAP[this.status]?.label || this.status
    },
  },
}
</script>

<style scoped>
.status-badge {
  display: inline-block;
  padding: 6rpx 16rpx;
  border-radius: 8rpx;
  font-size: 22rpx;
  font-weight: 600;
  color: #fff;
  line-height: 1.4;
}
.badge-draft { background: #d9d9d9; color: #666; }
.badge-pending { background: #faad14; }
.badge-modify-required { background: #fa8c16; }
.badge-appealing { background: #1890ff; }
.badge-first_pass, .badge-published, .badge-second_pass { background: #52c41a; }
.badge-first_reject, .badge-second_reject { background: #ff4d4f; }
</style>
