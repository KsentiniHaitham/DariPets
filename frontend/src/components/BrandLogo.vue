<script setup>
// Logo Dari : une maison (داري = « ma maison ») abritant une empreinte de patte.
defineProps({
  size: { type: [Number, String], default: 32 },
  color: { type: String, default: '#7C3AED' },
  pawColor: { type: String, default: '#ffffff' },
})
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Dari">
    <!-- toit -->
    <path :fill="color" d="M24 3.5 45 21.5a2.2 2.2 0 0 1-1.4 3.9H4.4A2.2 2.2 0 0 1 3 21.5Z" />
    <!-- corps de la maison -->
    <rect :fill="color" x="8.5" y="20" width="31" height="24" rx="3.5" />
    <!-- empreinte de patte (coussinet + 4 doigts) -->
    <ellipse :fill="pawColor" cx="24" cy="35" rx="6.2" ry="4.8" />
    <circle :fill="pawColor" cx="16.5" cy="29" r="2.7" />
    <circle :fill="pawColor" cx="20.8" cy="25.3" r="2.7" />
    <circle :fill="pawColor" cx="27.2" cy="25.3" r="2.7" />
    <circle :fill="pawColor" cx="31.5" cy="29" r="2.7" />
  </svg>
</template>
