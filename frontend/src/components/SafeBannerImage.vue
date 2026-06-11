<template>
  <img
    :src="displaySrc"
    :alt="alt"
    @error="onError"
  />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { getBannerFallbackDataUri } from '@/utils/bannerFallback'

const props = defineProps({
  src: {
    type: String,
    default: ''
  },
  fallbackKey: {
    type: String,
    default: 'default'
  },
  alt: {
    type: String,
    default: 'banner'
  }
})

const errored = ref(false)

const normalizedSrc = computed(() => (props.src || '').trim())

const displaySrc = computed(() => {
  if (!normalizedSrc.value || errored.value) {
    return getBannerFallbackDataUri(props.fallbackKey)
  }
  return normalizedSrc.value
})

watch(
  () => props.src,
  () => {
    errored.value = false
  }
)

function onError() {
  errored.value = true
}
</script>

