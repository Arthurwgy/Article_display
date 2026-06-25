### Laravel 部署流程

> ⚠️ 首次安装前，需在宝塔 PHP 8.5 配置中移除禁用函数：`putenv`、`proc_open`、`proc_get_status`

> ⚠️ 使用前需确认系统环境 PHP 版本优先为 8.5

1. **宝塔添加站点**
   - PHP版本选择 8.5
   - 数据库 MySQL utf8mb4

2. **添加源代码**
   
- 原地解压 Laravel 源码包
  
3. **站点修改**
   
   - 网站目录指向 `public`，运行目录指向 `/`
   - 伪静态选择 `laravel5`

4. **安装 Laravel**（命令行执行）

   ```bash
   # 安装 PHP 依赖
   composer install

   # 复制环境配置文件
   cp .env.example .env

   # 生成应用密钥
   php artisan key:generate

   # 运行数据库迁移
   php artisan migrate

   # 安装前端依赖并构建
   npm install
   npm run build
   ```

5. **配置 .env**
   
- 修改 `DB_xxx` 数据库连接部分
  
6. **启动服务器**

- ```bash
  php artisan serve
  ```

- 访问127.0.0.1:8000

---

### 插件

## Filament

### 安装步骤

1. **安装 Filament**
   ```bash
   composer require filament/filament
   ```

2. **发布前端资源**
   ```bash
   php artisan filament:install --assets
   ```

3. **创建管理员账号**
   ```bash
   php artisan make:filament-user
   ```
   按提示输入姓名、邮箱、密码即可
   首次创建账号时输入
   姓名: admin
   邮箱: admin@admin.com
   密码: 123456

4. **启动服务**
   ```bash
   php artisan serve
   ```

5. **访问后台**
   - 登录页：`http://127.0.0.1:8000/admin/login`
   - 用创建的账号登录



