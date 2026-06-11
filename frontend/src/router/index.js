import { createRouter, createWebHistory } from 'vue-router'

// ---- 移动端 H5 路由 ----
const mobileRoutes = [
  {
    path: '/',
    component: () => import('@/views/mobile/MobileLayout.vue'),
    children: [
      {
        path: '',
        name: 'Home',
        component: () => import('@/views/mobile/HomePage.vue'),
        meta: { title: '首页', tabbar: 'home' }
      },
      {
        path: 'report',
        name: 'ReportEntry',
        component: () => import('@/views/mobile/ReportEntryPage.vue'),
        meta: { title: '我要报告', tabbar: 'report' }
      }
    ]
  },
  {
    path: '/report/:categoryId',
    name: 'ReportNotice',
    component: () => import('@/views/mobile/ReportNoticePage.vue'),
    meta: { title: '注意事项' }
  },
  {
    path: '/report/:categoryId/form',
    name: 'ReportForm',
    component: () => import('@/views/mobile/ReportFormPage.vue'),
    meta: { title: '填写报告' }
  }
]

// ---- 后台管理路由 ----
const adminRoutes = [
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/views/admin/LoginPage.vue'),
    meta: { title: '管理员登录' }
  },
  {
    path: '/admin',
    component: () => import('@/views/admin/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard'
      },
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('@/views/admin/DashboardPage.vue'),
        meta: { title: '控制台' }
      },
      {
        path: 'content',
        name: 'ContentManage',
        component: () => import('@/views/admin/ContentManagePage.vue'),
        meta: { title: '内容管理', permission: 'content' }
      },
      {
        path: 'form-config',
        name: 'FormConfig',
        component: () => import('@/views/admin/FormConfigPage.vue'),
        meta: { title: '表单配置', permission: 'form' }
      },
      {
        path: 'admins',
        name: 'AdminManage',
        component: () => import('@/views/admin/AdminManagePage.vue'),
        meta: { title: '管理员管理', permission: 'admin' }
      },
      {
        path: 'reports',
        name: 'ReportManage',
        component: () => import('@/views/admin/ReportManagePage.vue'),
        meta: { title: '报表管理', permission: 'report' }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes: [...mobileRoutes, ...adminRoutes]
})

// 路由守卫 - 管理后台鉴权
router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('admin_token')
    if (!token) {
      next({ name: 'AdminLogin', query: { redirect: to.fullPath } })
      return
    }
    const adminInfo = JSON.parse(localStorage.getItem('admin_info') || '{}')
    const needPermission = to.meta.permission
    if (needPermission && adminInfo.role !== 'super_admin') {
      const permissions = adminInfo.permissions || []
      if (!permissions.includes(needPermission)) {
        next({ name: 'AdminDashboard' })
        return
      }
    }
  }
  // 更新页面标题
  if (to.meta.title) {
    document.title = to.meta.title + ' - 填报平台'
  }
  next()
})

export default router
