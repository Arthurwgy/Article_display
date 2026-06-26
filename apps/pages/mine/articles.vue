<template>
  <view class="page">
    <!-- 状态 tab -->
    <view class="status-tabs">
      <view
        v-for="tab in statusTabs"
        :key="tab.value"
        class="status-tab"
        :class="{ active: currentStatus === tab.value }"
        @click="switchStatus(tab.value)"
      >{{ tab.label }}</view>
    </view>

    <!-- 列表 -->
    <scroll-view
      class="article-list"
      scroll-y
      @scrolltolower="loadMore"
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
    >
      <view v-if="loading && articleList.length === 0" class="empty-state">加载中...</view>
      <view v-else-if="articleList.length === 0" class="empty-state">
        <view class="empty-icon">📝</view>
        <view class="empty-text">还没有文章</view>
        <view class="empty-btn" @click="goWrite">去写文章</view>
      </view>
      <view
        v-for="article in articleList"
        :key="article.id"
        class="article-item"
        @click="goDetail(article.id)"
      >
        <view class="item-left">
          <view class="item-title">{{ article.title }}</view>
          <view class="item-meta">
            <text class="status-dot" :class="'dot-' + article.status"></text>
            <text class="status-label">{{ statusText(article.status) }}</text>
            <text class="meta-sep">·</text>
            <text class="meta-text">{{ article.view_count }} 阅读</text>
            <text class="meta-sep" v-if="article.is_paid">·</text>
            <text class="meta-text" v-if="article.is_paid">🪙 {{ article.price_gold }}</text>
          </view>
          <view class="item-tags" v-if="article.tags && article.tags.length">
            <text v-for="tag in article.tags.slice(0, 3)" :key="tag" class="tag">{{ tag }}</text>
          </view>
        </view>
        <view class="item-arrow">›</view>
      </view>

      <view v-if="loadingMore" class="load-more">加载中...</view>
      <view v-else-if="noMore && articleList.length > 0" class="load-more no-more">— 没有更多了 —</view>
    </scroll-view>

    <!-- 写文章按钮 -->
    <view class="fab" @click="goWrite">
      <text class="fab-icon">✏️</text>
    </view>
  </view>
</template>

<script>
import { articleApi } from '@/api/article.js'
import { useUserStore } from '@/store/modules/user.js'

const STATUS_TABS = [
  { label: '全部', value: '' },
  { label: '草稿', value: 'draft' },
  { label: '审核中', value: 'pending' },
  { label: '已发布', value: 'published' },
  { label: '已驳回', value: 'first_reject' },
]

const STATUS_TEXT = {
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
      statusTabs: STATUS_TABS,
      currentStatus: '',
      articleList: [],
      page: 1,
      perPage: 20,
      total: 0,
      loading: false,
      refreshing: false,
      loadingMore: false,
      noMore: false,
    }
  },

  onLoad() {
    // 未登录直接跳转登录页
    if (!useUserStore().isLoggedIn) {
      uni.reLaunch({ url: '/pages/auth/login' })
      return
    }
    this.loadArticles()
  },

  onShow() {
    if (this._needsRefresh) {
      this._needsRefresh = false
      this.onRefresh()
    }
  },

  methods: {
    async loadArticles(resetPage = false) {
      if (this.loading) return
      if (resetPage) {
        this.page = 1
        this.noMore = false
      }
      this.loading = true
      try {
        const me = useUserStore().userInfo
        const params = {
          page: this.page,
          per_page: this.perPage,
          author_id: me.id,
          sort: 'latest',
        }
        if (this.currentStatus) params.status = this.currentStatus
        else params.status = 'all'

        const res = await articleApi.list(params)
        const data = res.data || []
        if (resetPage) {
          this.articleList = data
        } else {
          this.articleList.push(...data)
        }
        this.total = res.meta?.total || 0
        if (this.articleList.length >= this.total) {
          this.noMore = true
        }
      } catch (e) {
        uni.showToast({ title: '加载失败', icon: 'none' })
      } finally {
        this.loading = false
        this.refreshing = false
        this.loadingMore = false
      }
    },

    onRefresh() {
      this.refreshing = true
      this.loadArticles(true)
    },

    loadMore() {
      if (this.noMore || this.loading) return
      this.loadingMore = true
      this.page++
      this.loadArticles()
    },

    switchStatus(status) {
      this.currentStatus = status
      this.loadArticles(true)
    },

    statusText(status) {
      return STATUS_TEXT[status] || status
    },

    goDetail(id) {
      uni.navigateTo({ url: `/pages/article/detail?id=${id}` })
    },

    goWrite() {
      uni.navigateTo({ url: '/pages/article/edit' })
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

.status-tabs {
  display: flex;
  background: #fff;
  border-bottom: 1rpx solid #eee;
  padding: 0 10rpx;
  overflow-x: auto;
}
.status-tab {
  flex-shrink: 0;
  padding: 24rpx 24rpx;
  font-size: 28rpx;
  color: #999;
  position: relative;
}
.status-tab.active {
  color: #007aff;
  font-weight: 600;
}
.status-tab.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 40rpx;
  height: 4rpx;
  background: #007aff;
  border-radius: 2rpx;
}

.article-list {
  flex: 1;
  padding: 16rpx 0;
}

.article-item {
  display: flex;
  align-items: center;
  background: #fff;
  padding: 28rpx 30rpx;
  border-bottom: 1rpx solid #f5f5f5;
  cursor: pointer;
}
.article-item:active { background: #fafafa; }
.item-left { flex: 1; min-width: 0; }
.item-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 10rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.item-meta {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 10rpx;
  flex-wrap: wrap;
}
.status-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 50%;
  background: #d9d9d9;
  display: inline-block;
}
.dot-published { background: #52c41a; }
.dot-pending { background: #faad14; }
.dot-draft { background: #d9d9d9; }
.dot-first_reject, .dot-second_reject { background: #ff4d4f; }
.dot-modify_required { background: #fa8c16; }
.dot-appealing { background: #1890ff; }
.dot-first_pass, .dot-second_pass { background: #52c41a; }

.status-label { font-size: 24rpx; color: #666; }
.meta-sep { color: #ddd; font-size: 24rpx; }
.meta-text { font-size: 24rpx; color: #999; }

.item-tags { display: flex; flex-wrap: wrap; gap: 8rpx; }
.tag {
  padding: 4rpx 16rpx;
  background: #f0f7ff;
  color: #007aff;
  border-radius: 20rpx;
  font-size: 22rpx;
}

.item-arrow {
  font-size: 40rpx;
  color: #ccc;
  margin-left: 16rpx;
  flex-shrink: 0;
}

/* 空状态 */
.empty-state {
  text-align: center;
  padding: 160rpx 0 80rpx;
  color: #999;
}
.empty-icon { font-size: 100rpx; margin-bottom: 24rpx; }
.empty-text { font-size: 28rpx; margin-bottom: 32rpx; }
.empty-btn {
  display: inline-block;
  padding: 16rpx 48rpx;
  background: #007aff;
  color: #fff;
  border-radius: 40rpx;
  font-size: 28rpx;
}

.load-more { text-align: center; padding: 24rpx 0; font-size: 26rpx; color: #999; }
.no-more { color: #ccc; }

/* 写文章悬浮按钮 */
.fab {
  position: fixed;
  right: 40rpx;
  bottom: calc(40rpx + env(safe-area-inset-bottom));
  width: 100rpx;
  height: 100rpx;
  background: #007aff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4rpx 20rpx rgba(0, 122, 255, 0.4);
  z-index: 100;
}
.fab-icon { font-size: 48rpx; }
</style>
