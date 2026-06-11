<template>
  <van-overlay :show="show" @click="() => {}">
    <div class="notice-modal" @click.stop>
      <div class="modal-header">
        <h3>平台须知</h3>
      </div>
      <div class="modal-body">
        <div class="modal-content" v-html="formattedContent"></div>
      </div>
      <div class="modal-footer">
        <van-button plain type="default" size="small" @click="onReject">{{ rejectText }}</van-button>
        <van-button type="primary" size="small" @click="onAgree">{{ agreeText }}</van-button>
      </div>
    </div>
  </van-overlay>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  content: { type: String, default: '' },
  rejectText: { type: String, default: '拒绝' },
  agreeText: { type: String, default: '同意' }
})

const emit = defineEmits(['update:show'])

const formattedContent = computed(() => {
  return (props.content || '加载中...').replace(/\n/g, '<br/>')
})
const rejectText = computed(() => props.rejectText || '拒绝')
const agreeText = computed(() => props.agreeText || '同意')

function onReject() {
  emit('update:show', false)
  // 拒绝后可跳转或关闭
}

function onAgree() {
  localStorage.setItem('notice_agreed', 'true')
  emit('update:show', false)
}
</script>

<style scoped>
.notice-modal {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 85%;
  max-width: 400px;
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}
.modal-header {
  background: linear-gradient(135deg, #1989fa, #07c160);
  padding: 16px 20px;
  text-align: center;
}
.modal-header h3 { color: #fff; font-size: 18px; margin: 0; }
.modal-body {
  padding: 20px;
  max-height: 50vh;
  overflow-y: auto;
}
.modal-content { font-size: 14px; color: #666; line-height: 1.8; }
.modal-footer {
  display: flex;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid #f0f0f0;
}
.modal-footer .van-button { flex: 1; }
</style>
