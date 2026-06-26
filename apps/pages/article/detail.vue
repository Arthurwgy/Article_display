<template>
  <view class="page">
    <!-- 加载中 -->
    <view v-if="loading" class="loading-state">加载中...</view>

    <!-- 文章内容 -->
    <template v-else-if="article">
      <scroll-view class="article-scroll" scroll-y>
        <!-- 封面 -->
        <view class="cover-wrap">
          <image
            v-if="article.cover_image"
            class="cover-img"
            :src="article.cover_image"
            mode="aspectFill"
          />
          <view v-else class="cover-placeholder">
            <text>{{ article.title.slice(0, 1) }}</text>
          </view>
          <!-- 状态徽章 -->
          <view class="status-overlay">
            <view class="status-badge" :class="'status-' + article.status">
              {{ statusText }}
            </view>
          </view>
        </view>

        <!-- 头部信息 -->
        <view class="article-header">
          <view class="article-title">{{ article.title }}</view>

          <view class="author-row">
            <view class="author-avatar" :style="{ background: getAvatarColor(article.author.id) }">
              <text>{{ getInitial(article.author.name) }}</text>
            </view>
            <view class="author-info">
              <text class="author-name">{{ article.author.name }}</text>
              <text class="author-bio" v-if="article.author.bio">{{ article.author.bio }}</text>
            </view>
          </view>

          <view class="meta-row">
            <text class="meta-item">发布于 {{ formatDate(article.published_at || article.created_at) }}</text>
            <text class="meta-sep">·</text>
            <text class="meta-item">{{ article.view_count }} 阅读</text>
            <text class="meta-sep" v-if="article.review_count > 0">·</text>
            <text class="meta-item" v-if="article.review_count > 0">审核 {{ article.review_count }} 次</text>
          </view>

          <view class="tag-row">
            <text v-if="article.category" class="tag tag-category">{{ article.category.name }}</text>
            <text v-for="tag in (article.tags || [])" :key="tag" class="tag">{{ tag }}</text>
          </view>
        </view>

        <!-- 正文 -->
        <view class="article-body">
          <!-- 付费遮罩 -->
          <view v-if="isPaidLocked" class="paid-overlay">
            <view class="paid-icon">🔒</view>
            <view class="paid-title">付费内容</view>
            <view class="paid-desc">本文需支付 {{ article.price_gold }} 金币解锁</view>
            <view class="paid-btn" @click="unlockArticle">支付 {{ article.price_gold }} 金币解锁</view>
            <view class="paid-hint" v-if="!isLoggedIn">登录后可解锁付费内容</view>
          </view>

          <!-- 正文（已解锁或免费） -->
          <view v-else class="content-wrap">
            <view class="markdown-body" v-if="article.content_html" v-html="article.content_html"></view>
            <view v-else class="content-empty">暂无正文内容</view>
          </view>
        </view>
      </scroll-view>

      <!-- 底部操作栏 -->
      <view class="action-bar">
        <view v-if="canEdit" class="action-btn" @click="goEdit">
          <text class="action-icon">✏️</text>
          <text class="action-text">编辑</text>
        </view>
        <!-- 审核状态入口（非 published 文章，且是作者本人） -->
        <view
          v-if="canViewReviewStatus"
          class="action-btn"
          @click="goReviewStatus"
        >
          <text class="action-icon">📋</text>
          <text class="action-text">审核进度</text>
        </view>
        <view v-if="canSubmit" class="action-btn" @click="submitArticle">
          <text class="action-icon">📤</text>
          <text class="action-text">提交审核</text>
        </view>
        <view v-if="canDelete" class="action-btn action-btn-danger" @click="deleteArticle">
          <text class="action-icon">🗑️</text>
          <text class="action-text">删除</text>
        </view>
        <view class="action-btn" @click="goWriteArticle">
          <text class="action-icon">✍️</text>
          <text class="action-text">写文章</text>
        </view>
      </view>
    </template>

    <!-- 加载失败 -->
    <view v-else class="error-state">
      <view class="error-text">文章不存在或已下架</view>
      <view class="error-btn" @click="goBack">返回首页</view>
    </view>
  </view>
</template>

<script>
import { articleApi } from '@/api/article.js'
import { useUserStore } from '@/store/modules/user.js'

const AVATAR_COLORS = ['#5B8FF9', '#5AD8A6', '#F6BD16', '#E8684A', '#6DC8EC', '#9270CA']

const STATUS_MAP = {
  draft: '草稿',
  pending: '初审中',
  first_pass: '初审通过',
  published: '已发布',
  first_reject: '初审驳回',
  modify_required: '要求修改',
  appealing: '申诉中',
  second_pass: '二审通过',
  second_reject: '最终驳回',
}

export default {
  data() {
    return {
      article: null,
      loading: true,
    }
  },

  computed: {
    isLoggedIn() {
      return useUserStore().isLoggedIn
    },
    isPaidLocked() {
      if (!this.article) return false
      return this.article.is_paid && !this.article.is_paid_by_me
    },
    statusText() {
      return STATUS_MAP[this.article?.status] || this.article?.status || ''
    },
    canEdit() {
      const me = useUserStore().userInfo
      if (!me || !this.article) return false
      return this.article.author.id === me.id || me.role === 'admin'
    },
    canSubmit() {
      const me = useUserStore().userInfo
      if (!me || !this.article) return false
      return this.article.author.id === me.id && this.article.status === 'draft'
    },
    canDelete() {
      const me = useUserStore().userInfo
      if (!me || !this.article) return false
      return (this.article.author.id === me.id || me.role === 'admin') && this.article.status === 'draft'
    },
    canViewReviewStatus() {
      const me = useUserStore().userInfo
      if (!me || !this.article) return false
      if (this.article.status === 'published') return false
      return this.article.author.id === me.id || me.role === 'admin'
    },
  },

  onLoad(query) {
    this.articleId = query.id
    this.loadDetail()
  },

  methods: {
    async loadDetail() {
      this.loading = true
      this.article = null
      try {
        const res = await articleApi.detail(this.articleId)
        this.article = res
      } catch (e) {
        // not found or error
      } finally {
        this.loading = false
      }
    },

    async submitArticle() {
      try {
        await articleApi.submit(this.articleId)
        uni.showToast({ title: '提交成功', icon: 'success' })
        this.loadDetail()
      } catch (e) {
        uni.showToast({ title: (e && e.message) || '提交失败', icon: 'none' })
      }
    },

    async deleteArticle() {
      const res = await new Promise((resolve) => {
        uni.showModal({
          title: '确认删除',
          content: '确定要删除这篇草稿吗？此操作不可恢复。',
          confirmColor: '#f56c6c',
          success: (r) => resolve(r.confirm),
        })
      })
      if (!res) return
      try {
        await articleApi.remove(this.articleId)
        uni.showToast({ title: '已删除', icon: 'success' })
        // 通知列表页刷新
        const pages = getCurrentPages()
        const indexPage = pages.find(p => p.route === 'pages/index/index')
        if (indexPage) indexPage._needsRefresh = true
        setTimeout(() => uni.switchTab({ url: '/pages/index/index' }), 1500)
      } catch (e) {
        const msg = e?.message || '删除失败'
        uni.showToast({ title: msg, icon: 'none' })
      }
    },

    unlockArticle() {
      if (!this.isLoggedIn) {
        uni.navigateTo({ url: '/pages/auth/login' })
        return
      }
      // M5 付费逻辑，提示暂未支持
      uni.showToast({ title: '金币支付功能即将上线', icon: 'none' })
    },

    goEdit() {
      uni.navigateTo({ url: `/pages/article/edit?id=${this.articleId}` })
    },

    goReviewStatus() {
      uni.navigateTo({ url: `/pages/article/review-status?id=${this.articleId}` })
    },

    goWriteArticle() {
      uni.navigateTo({ url: '/pages/article/edit' })
    },

    goBack() {
      uni.switchTab({ url: '/pages/index/index' })
    },

    formatDate(dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr)
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    },

    getInitial(name) {
      return (name || '?').slice(0, 1).toUpperCase()
    },

    getAvatarColor(id) {
      const idx = (id || 'x').charCodeAt(0) % AVATAR_COLORS.length
      return AVATAR_COLORS[idx]
    },
  },
}
</script>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f5f5f5;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  gap: 24rpx;
  color: #999;
  font-size: 28rpx;
}
.error-btn {
  padding: 16rpx 48rpx;
  background: #007aff;
  color: #fff;
  border-radius: 40rpx;
  font-size: 28rpx;
}

/* 封面 */
.article-scroll {
  flex: 1;
  height: 0;
}
.cover-wrap {
  position: relative;
  height: 480rpx;
  overflow: hidden;
}
.cover-img {
  width: 100%;
  height: 100%;
}
.cover-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 120rpx;
  color: rgba(255, 255, 255, 0.5);
  font-weight: bold;
}
.status-overlay {
  position: absolute;
  top: 20rpx;
  right: 20rpx;
}
.status-badge {
  padding: 8rpx 20rpx;
  border-radius: 8rpx;
  font-size: 24rpx;
  font-weight: 600;
  color: #fff;
}
.status-published { background: #52c41a; }
.status-pending { background: #faad14; }
.status-draft { background: #d9d9d9; color: #666; }
.status-first_pass { background: #52c41a; }
.status-first_reject { background: #ff4d4f; }
.status-modify_required { background: #fa8c16; }
.status-appealing { background: #1890ff; }
.status-second_pass { background: #52c41a; }
.status-second_reject { background: #ff4d4f; }

/* 头部 */
.article-header {
  background: #fff;
  padding: 32rpx 30rpx;
  border-bottom: 1rpx solid #f0f0f0;
}
.article-title {
  font-size: 40rpx;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1.4;
  margin-bottom: 28rpx;
}

.author-row {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin-bottom: 20rpx;
}
.author-avatar {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 28rpx;
  color: #fff;
  font-weight: 600;
}
.author-info { display: flex; flex-direction: column; gap: 4rpx; }
.author-name { font-size: 28rpx; font-weight: 600; color: #333; }
.author-bio { font-size: 24rpx; color: #999; }

.meta-row {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 16rpx;
  flex-wrap: wrap;
}
.meta-item { font-size: 24rpx; color: #999; }
.meta-sep { color: #ddd; }

.tag-row { display: flex; flex-wrap: wrap; gap: 8rpx; }
.tag {
  padding: 6rpx 20rpx;
  border-radius: 20rpx;
  font-size: 24rpx;
}
.tag-category { background: #fff7e6; color: #fa8c16; }
.tag:not(.tag-category) { background: #f0f7ff; color: #007aff; }

/* 正文 */
.article-body {
  background: #fff;
  margin: 16rpx 0;
}
.paid-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 80rpx 40rpx;
  gap: 20rpx;
}
.paid-icon { font-size: 80rpx; }
.paid-title { font-size: 36rpx; font-weight: 700; color: #333; }
.paid-desc { font-size: 28rpx; color: #999; }
.paid-btn {
  padding: 20rpx 60rpx;
  background: #007aff;
  color: #fff;
  border-radius: 44rpx;
  font-size: 30rpx;
  font-weight: 600;
  margin-top: 16rpx;
}
.paid-hint { font-size: 24rpx; color: #bbb; }

.content-wrap { padding: 32rpx 30rpx; }
.content-empty { color: #999; font-size: 28rpx; text-align: center; padding: 60rpx 0; }

/* Markdown 正文样式 */
.markdown-body {
  font-size: 30rpx;
  color: #333;
  line-height: 1.8;
  word-break: break-word;
}
:deep(.markdown-body h1) { font-size: 44rpx; font-weight: 700; margin: 40rpx 0 20rpx; border-bottom: 1rpx solid #eee; padding-bottom: 16rpx; }
:deep(.markdown-body h2) { font-size: 38rpx; font-weight: 600; margin: 36rpx 0 16rpx; }
:deep(.markdown-body h3) { font-size: 34rpx; font-weight: 600; margin: 32rpx 0 12rpx; }
:deep(.markdown-body p) { margin: 20rpx 0; }
:deep(.markdown-body code) { background: #f5f5f5; padding: 4rpx 12rpx; border-radius: 6rpx; font-size: 28rpx; font-family: monospace; }
:deep(.markdown-body pre) { background: #f5f5f5; padding: 24rpx; border-radius: 12rpx; overflow-x: auto; margin: 20rpx 0; }
:deep(.markdown-body pre code) { background: none; padding: 0; }
:deep(.markdown-body blockquote) { border-left: 6rpx solid #007aff; padding: 12rpx 20rpx; margin: 20rpx 0; color: #666; background: #f9f9f9; }
:deep(.markdown-body img) { max-width: 100%; border-radius: 8rpx; }
:deep(.markdown-body a) { color: #007aff; }
:deep(.markdown-body ul), :deep(.markdown-body ol) { padding-left: 40rpx; margin: 16rpx 0; }
:deep(.markdown-body li) { margin: 8rpx 0; }
:deep(.markdown-body table) { width: 100%; border-collapse: collapse; margin: 20rpx 0; font-size: 28rpx; }
:deep(.markdown-body th), :deep(.markdown-body td) { border: 1rpx solid #eee; padding: 12rpx 16rpx; text-align: left; }
:deep(.markdown-body th) { background: #f9f9f9; font-weight: 600; }

/* 底部操作栏 */
.action-bar {
  display: flex;
  align-items: center;
  background: #fff;
  border-top: 1rpx solid #eee;
  padding: 16rpx 30rpx;
  padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
  gap: 32rpx;
}
.action-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  color: #666;
  font-size: 26rpx;
  cursor: pointer;
  flex: 1;
  justify-content: center;
}
.action-btn:active { opacity: 0.7; }
.action-btn-danger { color: #ff4d4f; }
.action-icon { font-size: 32rpx; }
.action-text { font-size: 26rpx; }
</style>
