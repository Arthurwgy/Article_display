import MarkdownIt from 'markdown-it'

const md = new MarkdownIt({
  html: false,
  breaks: true,
  linkify: true,
  typographer: true,
})

export function renderMarkdown(content) {
  if (!content) return ''
  const html = md.render(content)
  // H5: sanitize with DOMPurify
  // #ifdef H5
  try {
    const DOMPurify = require('dompurify')
    return DOMPurify.default ? DOMPurify.default.sanitize(html) : DOMPurify.sanitize(html)
  } catch {
    return html
  }
  // #endif
  // 非 H5 平台直接返回（mp-html/towxml 后续处理）
  // #ifndef H5
  return html
  // #endif
}
