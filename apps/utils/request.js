import config from '@/config/index.js'
import { getToken, removeToken } from './auth.js'

const { BASE_URL } = config

const LOGIN_PATH = '/pages/auth/login'

function request(options) {
  const token = getToken()

  return new Promise((resolve, reject) => {
    uni.request({
      url: BASE_URL + options.url,
      method: options.method || 'GET',
      data: options.data,
      header: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(options.header || {}),
      },
      success(res) {
        const { statusCode, data } = res

        if (statusCode === 204) {
          resolve(null)
          return
        }

        if (statusCode === 401) {
          removeToken()
          uni.reLaunch({ url: LOGIN_PATH })
          reject(new Error('Unauthorized'))
          return
        }

        if (statusCode === 422) {
          reject(data)
          return
        }

        if (statusCode >= 500) {
          uni.showToast({ title: '服务器错误', icon: 'none' })
          reject(data)
          return
        }

        if (statusCode === 200 || statusCode === 201) {
          resolve(data)
          return
        }

        if (statusCode === 403) {
          uni.showToast({ title: (data && data.message) || '无权限', icon: 'none' })
          reject(data)
          return
        }

        reject(data)
      },
      fail(err) {
        uni.showToast({ title: '网络异常', icon: 'none' })
        reject(err)
      },
    })
  })
}

export const get = (url, data) => request({ url, method: 'GET', data })
export const post = (url, data) => request({ url, method: 'POST', data })
export const put = (url, data) => request({ url, method: 'PUT', data })
export const del = (url, data) => request({ url, method: 'DELETE', data })

export default request
