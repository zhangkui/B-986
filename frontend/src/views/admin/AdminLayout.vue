<template>
  <el-container class="admin-layout">
    <el-aside width="220px" class="admin-aside">
      <div class="logo">
        <h3>管理后台</h3>
      </div>
      <el-menu :default-active="activeMenu" router class="admin-menu">
        <el-menu-item index="/admin/dashboard">
          <el-icon><i class="el-icon-monitor" /></el-icon>
          <span>控制台</span>
        </el-menu-item>
        <el-menu-item index="/admin/content">
          <el-icon><i class="el-icon-document" /></el-icon>
          <span>内容管理</span>
        </el-menu-item>
        <el-menu-item index="/admin/form-config">
          <el-icon><i class="el-icon-edit" /></el-icon>
          <span>表单配置</span>
        </el-menu-item>
        <el-menu-item index="/admin/admins">
          <el-icon><i class="el-icon-user" /></el-icon>
          <span>管理员管理</span>
        </el-menu-item>
        <el-menu-item index="/admin/reports">
          <el-icon><i class="el-icon-data-analysis" /></el-icon>
          <span>报表管理</span>
        </el-menu-item>
      </el-menu>
    </el-aside>
    <el-container>
      <el-header class="admin-header">
        <span class="header-title">{{ currentTitle }}</span>
        <el-button text @click="handleLogout">退出登录</el-button>
      </el-header>
      <el-main class="admin-main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const activeMenu = computed(() => route.path)
const currentTitle = computed(() => route.meta.title || '控制台')

function handleLogout() {
  localStorage.removeItem('admin_token')
  router.push('/admin/login')
}
</script>

<style scoped>
.admin-layout { min-height: 100vh; }
.admin-aside { background: #304156; }
.logo { height: 60px; display: flex; align-items: center; justify-content: center; background: #263445; }
.logo h3 { color: #fff; font-size: 16px; }
.admin-menu { border-right: none; background: #304156; }
.admin-menu .el-menu-item { color: #bfcbd9; }
.admin-menu .el-menu-item:hover { background: #263445; }
.admin-menu .el-menu-item.is-active { background: #1989fa; color: #fff; }
.admin-header { background: #fff; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.header-title { font-size: 18px; font-weight: 600; }
.admin-main { background: #f5f7fa; padding: 20px; }
</style>
