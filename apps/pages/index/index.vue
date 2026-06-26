<template>
  <view class="page">
    <!-- 搜索栏 -->
    <view class="search-bar">
      <input
        class="search-input"
        placeholder="搜索文章或作者"
        v-model="searchKeyword"
        @confirm="onSearch"
      />
      <view class="search-btn" @click="onSearch">搜索</view>
    </view>

    <!-- 分类 tab -->
    <scroll-view class="category-tabs" scroll-x>
      <view
        class="tab-item"
        :class="{ active: currentCategoryId === '' }"
        @click="switchCategory('')"
      >全部</view>
      <view
        v-for="cat in categoryList"
        :key="cat.id"
        class="tab-item"
        :class="{ active: currentCategoryId === cat.id }"
        @click="switchCategory(cat.id)"
      >{{ cat.name }}</view>
    </scroll-view>

    <!-- 排序 -->
    <view class="sort-bar">
      <view class="sort-item" :class="{ active: sortType === 'latest' }" @click="switchSort('latest')">最新</view>
      <view class="sort-divider">|</view>
      <view class="sort-item" :class="{ active: sortType === 'hot' }" @click="switchSort('hot')">最热</view>
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
        <view class="empty-icon">📭</view>
        <view class="empty-text">暂无文章</view>
      </view>
      <view
        v-for="article in articleList"
        :key="article.id"
        class="article-card"
        @click="goDetail(article.id)"
      >
        <!-- 封面 + 标签 -->
        <view class="card-cover-wrap">
          <image
            v-if="article.cover_image"
            class="card-cover"
            :src="article.cover_image"
            mode="aspectFill"
          />
          <view v-else class="card-cover-placeholder">
            <text class="placeholder-text">{{ article.title.slice(0, 1) }}</text>
          </view>
          <view v-if="article.is_top" class="badge badge-top">置顶</view>
          <view v-if="article.is_featured" class="badge badge-featured">精选</view>
          <view v-if="article.is_paid" class="badge badge-paid">
            <text class="coin-icon">🪙</text>{{ article.price_gold }}
          </view>
        </view>

        <!-- 内容 -->
        <view class="card-content">
          <view class="card-title">{{ article.title }}</view>

          <view class="card-author">
            <view class="author-avatar" :style="{ background: getAvatarColor(article.author.id) }">
              <text class="author-initial">{{ getInitial(article.author.name) }}</text>
            </view>
            <text class="author-name">{{ article.author.name }}</text>
            <text class="card-meta-sep">·</text>
            <text class="card-meta">{{ article.view_count }} 阅读</text>
          </view>

          <view class="card-tags">
            <text v-if="article.category" class="tag tag-category">{{ article.category.name }}</text>
            <text v-for="tag in (article.tags || []).slice(0, 3)" :key="tag" class="tag">{{ tag }}</text>
          </view>
        </view>
      </view>

      <view v-if="loadingMore" class="load-more">加载中...</view>
      <view v-else-if="noMore && articleList.length > 0" class="load-more no-more">— 没有更多了 —</view>
    </scroll-view>
  </view>
</template>

<script>
import { articleApi } from '@/api/article.js'
import { categoryApi } from '@/api/category.js'

const AVATAR_COLORS = ['#5B8FF9', '#5AD8A6', '#F6BD16', '#E8684A', '#6DC8EC', '#9270CA']

export default {
  data() {
    return {
      articleList: [],
      categoryList: [],
      searchKeyword: '',
      currentCategoryId: '',
      sortType: 'latest',
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
    this.loadCategories()
    this.loadArticles()
  },

  onShow() {
    if (this._needsRefresh) {
      this._needsRefresh = false
      this.onRefresh()
    }
  },

  methods: {
    async loadCategories() {
      try {
        const res = await categoryApi.list()
        this.categoryList = res.data || []
      } catch (e) {
        // ignore
      }
    },

    async loadArticles(resetPage = false) {
      if (this.loading) return
      if (resetPage) {
        this.page = 1
        this.noMore = false
      }
      this.loading = true
      try {
        const params = {
          page: this.page,
          per_page: this.perPage,
          sort: this.sortType,
        }
        if (this.currentCategoryId) params.category_id = this.currentCategoryId
        if (this.searchKeyword) params.q = this.searchKeyword

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

    onSearch() {
      this.loadArticles(true)
    },

    switchCategory(id) {
      this.currentCategoryId = id
      this.loadArticles(true)
    },

    switchSort(sort) {
      this.sortType = sort
      this.loadArticles(true)
    },

    goDetail(id) {
      uni.navigateTo({ url: `/pages/article/detail?id=${id}` })
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

.search-bar {
  display: flex;
  align-items: center;
  padding: 20rpx 30rpx;
  background: #fff;
  gap: 16rpx;
}
.search-input {
  flex: 1;
  height: 72rpx;
  padding: 0 28rpx;
  background: #f5f5f5;
  border-radius: 36rpx;
  font-size: 28rpx;
}
.search-btn {
  width: 120rpx;
  height: 72rpx;
  line-height: 72rpx;
  background: #007aff;
  color: #fff;
  border-radius: 36rpx;
  font-size: 28rpx;
  text-align: center;
}

.category-tabs {
  display: flex;
  white-space: nowrap;
  background: #fff;
  border-bottom: 1rpx solid #eee;
  padding: 0 20rpx;
}
.tab-item {
  display: inline-block;
  padding: 20rpx 28rpx;
  font-size: 28rpx;
  color: #666;
  position: relative;
}
.tab-item.active {
  color: #007aff;
  font-weight: 600;
}
.tab-item.active::after {
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

.sort-bar {
  display: flex;
  align-items: center;
  padding: 16rpx 30rpx;
  background: #fff;
  border-bottom: 1rpx solid #eee;
  gap: 20rpx;
}
.sort-item {
  font-size: 28rpx;
  color: #999;
}
.sort-item.active {
  color: #333;
  font-weight: 600;
}
.sort-divider {
  color: #ddd;
}

.article-list {
  flex: 1;
  padding: 20rpx 30rpx;
}

.article-card {
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
  margin-bottom: 24rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}
.card-cover-wrap {
  position: relative;
  height: 360rpx;
  overflow: hidden;
}
.card-cover {
  width: 100%;
  height: 100%;
}
.card-cover-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}
.placeholder-text {
  font-size: 80rpx;
  color: rgba(255, 255, 255, 0.6);
  font-weight: bold;
}
.badge {
  position: absolute;
  padding: 6rpx 16rpx;
  border-radius: 6rpx;
  font-size: 22rpx;
  font-weight: 600;
  color: #fff;
}
.badge-top { top: 16rpx; left: 16rpx; background: #ff6b6b; }
.badge-featured { top: 16rpx; right: 16rpx; background: #fab005; }
.badge-paid {
  bottom: 16rpx;
  right: 16rpx;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  gap: 4rpx;
}
.coin-icon { font-size: 22rpx; }

.card-content { padding: 24rpx; }
.card-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  line-height: 1.5;
  margin-bottom: 16rpx;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card-author {
  display: flex;
  align-items: center;
  margin-bottom: 12rpx;
  gap: 8rpx;
}
.author-avatar {
  width: 48rpx;
  height: 48rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.author-initial {
  font-size: 24rpx;
  color: #fff;
  font-weight: 600;
}
.author-name { font-size: 26rpx; color: #666; }
.card-meta-sep { color: #ccc; }
.card-meta { font-size: 24rpx; color: #999; }

.card-tags { display: flex; flex-wrap: wrap; gap: 8rpx; }
.tag {
  padding: 4rpx 16rpx;
  background: #f0f7ff;
  color: #007aff;
  border-radius: 20rpx;
  font-size: 24rpx;
}
.tag-category { background: #fff7e6; color: #fa8c16; }

.empty-state { text-align: center; padding: 120rpx 0; color: #999; }
.empty-icon { font-size: 100rpx; margin-bottom: 24rpx; }
.empty-text { font-size: 28rpx; }
.load-more { text-align: center; padding: 24rpx 0; font-size: 26rpx; color: #999; }
.no-more { color: #ccc; }
</style>
