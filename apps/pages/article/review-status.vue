<template>
  <view class="page">
    <!-- 顶部状态徽章 -->
    <view class="status-header">
      <view class="status-badge-wrap">
        <view class="status-badge" :class="'badge-' + article?.status">
          {{ statusText }}
        </view>
      </view>
      <view class="status-desc">{{ statusDesc }}</view>
    </view>

    <!-- 审核日志 -->
    <view class="section">
      <view class="section-title">审核记录</view>
      <view v-if="loading" class="loading-state">加载中...</view>
      <view v-else>
        <app-timeline :items="reviewLogs"></app-timeline>
      </view>
    </view>

    <!-- 底部操作区 -->
    <view class="bottom-bar">
      <!-- 初审驳回：申诉 -->
      <view v-if="article?.status === 'first_reject'" class="action-panel">
        <view class="action-hint">您对初审结果有异议？</view>
        <button class="btn btn-primary" @click="showAppealModal = true">申诉</button>
      </view>

      <!-- 要求修改：去编辑 -->
      <view v-else-if="article?.status === 'modify_required'" class="action-panel">
        <view class="action-hint modify">请按要求修改后重新提交</view>
        <button class="btn btn-primary" @click="goEdit">去修改</button>
      </view>

      <!-- 申诉中：等待 -->
      <view v-else-if="article?.status === 'appealing'" class="action-panel">
        <view class="action-hint info">申诉已提交，请等待二审</view>
      </view>

      <!-- 已发布：提示 -->
      <view v-else-if="article?.status === 'published'" class="action-panel">
        <view class="action-hint success">文章已成功发布，继续加油</view>
      </view>

      <!-- 二审驳回：最终 -->
      <view v-else-if="article?.status === 'second_reject'" class="action-panel">
        <view class="action-hint error">二审未通过，如需继续可删除草稿重新发布</view>
      </view>
    </view>

    <!-- 申诉弹窗 -->
    <view v-if="showAppealModal" class="modal-overlay" @click="showAppealModal = false">
      <view class="modal-content" @click.stop>
        <view class="modal-header">
          <text class="modal-title">提交申诉</text>
          <text class="modal-close" @click="showAppealModal = false">×</text>
        </view>
        <view class="modal-body">
          <textarea
            class="appeal-input"
            v-model="appealReason"
            placeholder="请说明申诉理由（选填）"
            :maxlength="500"
          />
          <view class="appeal-counter">{{ appealReason.length }}/500</view>
        </view>
        <view class="modal-footer">
          <button class="btn btn-outline" @click="showAppealModal = false">取消</button>
          <button class="btn btn-primary" :disabled="submitting" @click="submitAppeal">
            {{ submitting ? '提交中...' : '确认申诉' }}
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import AppTimeline from '@/components/timeline/timeline.vue'
import { articleApi } from '@/api/article.js'
import { reviewApi } from '@/api/review.js'
import { useUserStore } from '@/store/modules/user.js'

const STATUS_CONFIG = {
  draft:        { label: '草稿',           desc: '草稿尚未提交审核' },
  pending:      { label: '初审中',         desc: '文章正在等待审核' },
  first_pass:   { label: '初审通过',       desc: '初审已通过，等待发布' },
  published:    { label: '已发布',         desc: '文章已成功发布' },
  first_reject: { label: '初审驳回',       desc: '初审未通过，可申诉或修改重提' },
  modify_required: { label: '要求修改',    desc: '需按要求修改后重新提交' },
  appealing:    { label: '申诉中',         desc: '已提交申诉，等待二审' },
  second_pass:  { label: '二审通过',       desc: '二审已通过' },
  second_reject: { label: '最终驳回',       desc: '二审未通过' },
}

export default {
  components: { AppTimeline },
  data() {
    return {
      articleId: null,
      article: null,
      reviewLogs: [],
      loading: false,
      showAppealModal: false,
      appealReason: '',
      submitting: false,
    }
  },
  computed: {
    statusText() {
      return STATUS_CONFIG[this.article?.status]?.label || this.article?.status || ''
    },
    statusDesc() {
      return STATUS_CONFIG[this.article?.status]?.desc || ''
    },
  },
  onLoad(query) {
    this.articleId = query.id
    this.load()
  },
  methods: {
    async load() {
      this.loading = true
      try {
        const [detailRes, logsRes] = await Promise.all([
          articleApi.detail(this.articleId),
          reviewApi.logs(this.articleId),
        ])
        this.article = detailRes
        this.reviewLogs = logsRes.data || []
      } catch (e) {
        uni.showToast({ title: '加载失败', icon: 'none' })
      } finally {
        this.loading = false
      }
    },

    async submitAppeal() {
      if (this.submitting) return
      this.submitting = true
      try {
        await reviewApi.appeal(this.articleId, this.appealReason)
        uni.showToast({ title: '申诉已提交', icon: 'success' })
        this.showAppealModal = false
        this.appealReason = ''
        this.load()
      } catch (e) {
        uni.showToast({ title: e?.message || '申诉失败', icon: 'none' })
      } finally {
        this.submitting = false
      }
    },

    goEdit() {
      uni.navigateTo({ url: `/pages/article/edit?id=${this.articleId}&from=modify` })
    },
  },
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}

/* 顶部状态 */
.status-header {
  background: #fff;
  padding: 40rpx 30rpx 32rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  border-bottom: 1rpx solid #f0f0f0;
  margin-bottom: 16rpx;
}
.status-badge {
  display: inline-block;
  padding: 12rpx 36rpx;
  border-radius: 40rpx;
  font-size: 32rpx;
  font-weight: 700;
  color: #fff;
  margin-bottom: 16rpx;
}
.badge-draft { background: #d9d9d9; color: #666; }
.badge-pending { background: #faad14; }
.badge-first_pass { background: #52c41a; }
.badge-published { background: #52c41a; }
.badge-first_reject { background: #ff4d4f; }
.badge-modify_required { background: #fa8c16; }
.badge-appealing { background: #1890ff; }
.badge-second_pass { background: #52c41a; }
.badge-second_reject { background: #ff4d4f; }
.status-desc {
  font-size: 26rpx;
  color: #999;
  text-align: center;
}

/* 审核记录区 */
.section {
  background: #fff;
  padding: 24rpx 30rpx;
  margin-bottom: 16rpx;
}
.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 16rpx;
  padding-bottom: 16rpx;
  border-bottom: 1rpx solid #f0f0f0;
}
.loading-state {
  text-align: center;
  color: #999;
  font-size: 26rpx;
  padding: 40rpx 0;
}

/* 底部操作 */
.bottom-bar {
  background: #fff;
  padding: 24rpx 30rpx;
  padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
}
.action-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
}
.action-hint {
  font-size: 26rpx;
  color: #999;
  text-align: center;
}
.action-hint.modify { color: #fa8c16; }
.action-hint.info { color: #1890ff; }
.action-hint.success { color: #52c41a; }
.action-hint.error { color: #ff4d4f; }

/* 按钮 */
.btn {
  min-width: 240rpx;
  height: 80rpx;
  line-height: 80rpx;
  border-radius: 40rpx;
  font-size: 30rpx;
  font-weight: 600;
  border: none;
  text-align: center;
}
.btn-primary {
  background: #007aff;
  color: #fff;
}
.btn-outline {
  background: #fff;
  color: #666;
  border: 2rpx solid #ddd;
}
.btn[disabled] {
  opacity: 0.5;
}

/* 申诉弹窗 */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40rpx;
}
.modal-content {
  background: #fff;
  border-radius: 24rpx;
  width: 100%;
  max-width: 600rpx;
  overflow: hidden;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx 30rpx 24rpx;
  border-bottom: 1rpx solid #f0f0f0;
}
.modal-title {
  font-size: 32rpx;
  font-weight: 700;
  color: #333;
}
.modal-close {
  font-size: 48rpx;
  color: #ccc;
  line-height: 1;
}
.modal-body {
  padding: 24rpx 30rpx;
}
.appeal-input {
  width: 100%;
  min-height: 200rpx;
  padding: 20rpx;
  background: #f5f5f5;
  border-radius: 12rpx;
  font-size: 28rpx;
  color: #333;
  box-sizing: border-box;
  border: none;
  outline: none;
  resize: none;
  line-height: 1.6;
}
.appeal-counter {
  text-align: right;
  font-size: 24rpx;
  color: #bbb;
  margin-top: 8rpx;
}
.modal-footer {
  display: flex;
  gap: 24rpx;
  padding: 24rpx 30rpx 32rpx;
  justify-content: center;
}
</style>
