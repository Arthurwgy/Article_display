<template>
  <view class="page">
    <!-- 修改提示条 -->
    <view v-if="fromModify" class="modify-banner">
      <text class="modify-icon">📝</text>
      <text class="modify-text">修改后重新提交将覆盖原文章</text>
    </view>

    <!-- 标题 -->
    <view class="form-section">
      <input
        class="title-input"
        placeholder="请输入文章标题"
        v-model="form.title"
        maxlength="200"
      />
    </view>

    <!-- 标签页切换：编辑 / 预览 -->
    <view class="tab-bar">
      <view class="tab" :class="{ active: activeTab === 'edit' }" @click="activeTab = 'edit'">编辑</view>
      <view class="tab" :class="{ active: activeTab === 'preview' }" @click="switchToPreview">预览</view>
    </view>

    <!-- 编辑区 -->
    <view v-show="activeTab === 'edit'" class="editor-wrap">
      <textarea
        class="content-editor"
        placeholder="请输入正文内容（支持 Markdown）&#10;&#10;示例：&#10;# 标题&#10;**加粗**  *斜体*&#10;- 列表项&#10;`代码`"
        v-model="form.content"
        :maxlength="50000"
        placeholder-style="color:#bbb"
      />
    </view>

    <!-- 预览区 -->
    <view v-show="activeTab === 'preview'" class="preview-wrap">
      <view class="preview-title">{{ form.title || '无标题' }}</view>
      <view class="markdown-body" v-if="previewHtml" v-html="previewHtml"></view>
      <view class="preview-empty" v-else>暂无预览内容</view>
    </view>

    <!-- 分类选择 -->
    <view class="form-section">
      <view class="section-label">分类</view>
      <view class="picker-row" @click="showCategoryPicker = true">
        <text class="picker-value">{{ selectedCategoryName || '请选择分类（可选）' }}</text>
        <text class="picker-arrow">›</text>
      </view>
    </view>

    <!-- 标签输入 -->
    <view class="form-section">
      <view class="section-label">标签</view>
      <view class="tag-input-wrap">
        <view class="tag-list">
          <view v-for="tag in form.tags" :key="tag" class="tag-item">
            <text>{{ tag }}</text>
            <text class="tag-remove" @click="removeTag(tag)">×</text>
          </view>
        </view>
        <input
          class="tag-input"
          placeholder="输入标签后回车添加"
          v-model="tagInput"
          @confirm="addTag"
          @blur="addTag"
        />
      </view>
      <view class="tag-hint">输入后回车或失焦自动添加，支持多个标签</view>
    </view>

    <!-- 封面图 -->
    <view class="form-section">
      <view class="section-label">封面图 URL</view>
      <input
        class="form-input"
        placeholder="输入图片地址（可选）"
        v-model="form.cover_image"
      />
      <image
        v-if="form.cover_image"
        class="cover-preview"
        :src="form.cover_image"
        mode="aspectFill"
        @error="form.cover_image = ''"
      />
    </view>

    <!-- 金币定价 -->
    <view class="form-section">
      <view class="section-label">金币定价</view>
      <view class="price-row">
        <input
          class="form-input price-input"
          type="number"
          placeholder="0"
          v-model="form.price_gold"
          min="0"
        />
        <text class="price-unit">金币（0 = 免费）</text>
      </view>
    </view>

    <!-- 底部操作 -->
    <view class="bottom-bar">
      <view class="bottom-btn draft-btn" @click="saveDraft">保存草稿</view>
      <view class="bottom-btn submit-btn" @click="saveAndSubmit">
        {{ fromModify ? '保存并重新提交' : '提交审核' }}
      </view>
    </view>

    <!-- 分类选择弹窗 -->
    <view v-if="showCategoryPicker" class="picker-modal">
      <view class="picker-modal-mask" @click="showCategoryPicker = false"></view>
      <view class="picker-modal-content">
        <view class="picker-modal-header">
          <text>选择分类</text>
          <text class="picker-modal-close" @click="showCategoryPicker = false">×</text>
        </view>
        <scroll-view class="picker-modal-list" scroll-y>
          <view
            class="picker-option"
            :class="{ selected: form.category_id === '' }"
            @click="selectCategory('')"
          >不限分类</view>
          <view
            v-for="cat in categoryList"
            :key="cat.id"
            class="picker-option"
            :class="{ selected: form.category_id === cat.id }"
            @click="selectCategory(cat.id, cat.name)"
          >{{ cat.name }}</view>
        </scroll-view>
      </view>
    </view>
  </view>
</template>

<script>
import { articleApi } from '@/api/article.js'
import { categoryApi } from '@/api/category.js'
import { reviewApi } from '@/api/review.js'
import { renderMarkdown } from '@/utils/markdown.js'

export default {
  data() {
    return {
      articleId: null,
      activeTab: 'edit',
      previewHtml: '',
      showCategoryPicker: false,
      categoryList: [],
      selectedCategoryName: '',
      tagInput: '',
      form: {
        title: '',
        content: '',
        category_id: '',
        tags: [],
        cover_image: '',
        price_gold: 0,
        status: 'draft',
      },
      saving: false,
      fromModify: false,
    }
  },

  onLoad(query) {
    if (query.id) {
      this.articleId = query.id
      uni.setNavigationBarTitle({ title: '编辑文章' })
      this.loadArticle()
    } else {
      uni.setNavigationBarTitle({ title: '写文章' })
    }
    if (query.from === 'modify') {
      this.fromModify = true
      uni.setNavigationBarTitle({ title: '修改文章' })
    }
    this.loadCategories()
  },

  methods: {
    async loadArticle() {
      try {
        const res = await articleApi.detail(this.articleId)
        this.form.title = res.title || ''
        this.form.content = res.content || ''
        this.form.category_id = res.category?.id || ''
        this.selectedCategoryName = res.category?.name || ''
        this.form.tags = res.tags || []
        this.form.cover_image = res.cover_image || ''
        this.form.price_gold = res.price_gold || 0
      } catch (e) {
        uni.showToast({ title: '加载文章失败', icon: 'none' })
      }
    },

    async loadCategories() {
      try {
        const res = await categoryApi.list()
        this.categoryList = res.data || []
      } catch (e) {
        // ignore
      }
    },

    switchToPreview() {
      this.activeTab = 'preview'
      this.previewHtml = renderMarkdown(this.form.content)
    },

    selectCategory(id, name) {
      this.form.category_id = id
      this.selectedCategoryName = name || ''
      this.showCategoryPicker = false
    },

    addTag() {
      const tag = this.tagInput.trim()
      if (!tag) return
      if (this.form.tags.includes(tag)) {
        this.tagInput = ''
        return
      }
      if (this.form.tags.length >= 10) {
        uni.showToast({ title: '最多添加 10 个标签', icon: 'none' })
        return
      }
      this.form.tags.push(tag)
      this.tagInput = ''
    },

    removeTag(tag) {
      this.form.tags = this.form.tags.filter((t) => t !== tag)
    },

    async saveDraft() {
      const ok = await this.validate()
      if (!ok) return
      this.form.status = 'draft'
      await this.save()
    },

    async saveAndSubmit() {
      const ok = await this.validate()
      if (!ok) return
      this.form.status = 'draft'
      await this.save()
      // 保存成功后提交审核
      try {
        const submitId = this.articleId || this._lastCreatedId
        if (this.fromModify) {
          // modify 模式：修改后重新提交
          await reviewApi.resubmit(submitId)
          uni.showToast({ title: '修改已提交审核', icon: 'success' })
        } else {
          // 正常模式：首次提交审核
          await articleApi.submit(submitId)
          uni.showToast({ title: '提交审核成功', icon: 'success' })
        }
        // 通知列表页刷新
        const pages = getCurrentPages()
        const indexPage = pages.find((p) => p.route === 'pages/index/index')
        if (indexPage) indexPage._needsRefresh = true
        setTimeout(() => uni.switchTab({ url: '/pages/index/index' }), 1500)
      } catch (e) {
        uni.showToast({ title: (e && e.message) || '提交失败', icon: 'none' })
      }
    },

    async save() {
      if (this.saving) return
      this.saving = true
      uni.showLoading({ title: '保存中...' })
      try {
        const data = { ...this.form }

        let res
        if (this.articleId) {
          res = await articleApi.update(this.articleId, data)
        } else {
          res = await articleApi.create(data)
          this.articleId = res.id
          this._lastCreatedId = res.id
        }
        uni.hideLoading()
        uni.showToast({ title: '保存成功', icon: 'success' })
        // 通知列表页刷新
        const pages = getCurrentPages()
        const indexPage = pages.find((p) => p.route === 'pages/index/index')
        if (indexPage) indexPage._needsRefresh = true
      } catch (e) {
        uni.hideLoading()
        const msg = e?.data?.message || e?.message || '保存失败'
        uni.showToast({ title: msg, icon: 'none' })
      } finally {
        this.saving = false
      }
    },

    validate() {
      if (!this.form.title.trim()) {
        uni.showToast({ title: '请输入标题', icon: 'none' })
        return false
      }
      if (!this.form.content.trim()) {
        uni.showToast({ title: '请输入正文内容', icon: 'none' })
        return false
      }
      return true
    },
  },
}
</script>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 120rpx;
}

.modify-banner {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding: 20rpx 30rpx;
  background: #fff7e6;
  border-bottom: 1rpx solid #ffe58f;
}
.modify-icon { font-size: 28rpx; }
.modify-text {
  font-size: 26rpx;
  color: #fa8c16;
  font-weight: 500;
}

.form-section {
  background: #fff;
  padding: 24rpx 30rpx;
  margin-bottom: 2rpx;
}
.section-label {
  font-size: 28rpx;
  color: #333;
  font-weight: 600;
  margin-bottom: 16rpx;
}

.title-input {
  width: 100%;
  font-size: 36rpx;
  font-weight: 700;
  color: #1a1a1a;
  border: none;
  outline: none;
  padding: 0;
}
.title-input::placeholder { color: #bbb; }

.tab-bar {
  display: flex;
  background: #fff;
  border-bottom: 1rpx solid #eee;
}
.tab {
  flex: 1;
  text-align: center;
  padding: 20rpx 0;
  font-size: 28rpx;
  color: #999;
  position: relative;
}
.tab.active {
  color: #007aff;
  font-weight: 600;
}
.tab.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60rpx;
  height: 4rpx;
  background: #007aff;
  border-radius: 2rpx;
}

.editor-wrap {
  flex: 1;
  background: #fff;
}
.content-editor {
  width: 100%;
  min-height: 500rpx;
  padding: 24rpx 30rpx;
  font-size: 30rpx;
  line-height: 1.8;
  color: #333;
  border: none;
  resize: none;
  outline: none;
  background: #fff;
  box-sizing: border-box;
}

.preview-wrap {
  background: #fff;
  padding: 24rpx 30rpx;
  min-height: 500rpx;
}
.preview-title {
  font-size: 40rpx;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 24rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid #eee;
}
.preview-empty { color: #bbb; font-size: 28rpx; padding: 60rpx 0; text-align: center; }

/* Markdown 预览样式 */
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

/* 分类选择 */
.picker-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}
.picker-value { font-size: 28rpx; color: #333; }
.picker-value[placeholder] { color: #bbb; }
.picker-arrow { font-size: 36rpx; color: #ccc; }

/* 标签 */
.tag-input-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}
.tag-list { display: flex; flex-wrap: wrap; gap: 12rpx; }
.tag-item {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 20rpx;
  background: #f0f7ff;
  color: #007aff;
  border-radius: 20rpx;
  font-size: 26rpx;
}
.tag-remove {
  font-size: 28rpx;
  color: #007aff;
  margin-left: 4rpx;
}
.tag-input {
  flex: 1;
  min-width: 200rpx;
  height: 64rpx;
  padding: 0 20rpx;
  background: #f5f5f5;
  border-radius: 32rpx;
  font-size: 28rpx;
}
.tag-hint { font-size: 24rpx; color: #bbb; margin-top: 8rpx; }

/* 封面图 */
.form-input {
  width: 100%;
  height: 80rpx;
  padding: 0 24rpx;
  background: #f5f5f5;
  border-radius: 12rpx;
  font-size: 28rpx;
  box-sizing: border-box;
}
.cover-preview {
  width: 200rpx;
  height: 150rpx;
  border-radius: 12rpx;
  margin-top: 16rpx;
}

/* 金币定价 */
.price-row {
  display: flex;
  align-items: center;
  gap: 16rpx;
}
.price-input { width: 160rpx; text-align: center; }
.price-unit { font-size: 28rpx; color: #999; }

/* 底部操作 */
.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  gap: 24rpx;
  padding: 16rpx 30rpx;
  padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
  background: #fff;
  border-top: 1rpx solid #eee;
}
.bottom-btn {
  flex: 1;
  height: 88rpx;
  line-height: 88rpx;
  text-align: center;
  border-radius: 44rpx;
  font-size: 30rpx;
  font-weight: 600;
}
.draft-btn {
  background: #fff;
  color: #666;
  border: 2rpx solid #ddd;
}
.submit-btn {
  background: #007aff;
  color: #fff;
}

/* 分类选择弹窗 */
.picker-modal {
  position: fixed;
  inset: 0;
  z-index: 999;
}
.picker-modal-mask {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
}
.picker-modal-content {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  max-height: 60vh;
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  overflow: hidden;
}
.picker-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx 30rpx;
  border-bottom: 1rpx solid #eee;
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
}
.picker-modal-close { font-size: 48rpx; color: #ccc; }
.picker-modal-list { max-height: 50vh; padding: 16rpx 0; }
.picker-option {
  padding: 24rpx 30rpx;
  font-size: 30rpx;
  color: #333;
}
.picker-option.selected {
  color: #007aff;
  background: #f0f7ff;
}
</style>
