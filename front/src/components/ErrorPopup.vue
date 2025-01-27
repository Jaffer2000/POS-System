<template>
  <div v-if="visible" class="popup-overlay">
    <div class="popup-message">
      <p>{{ message }}</p>
      <button @click="closePopup">Sluiten</button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      visible: false,
      message: "",
    };
  },
  methods: {
    showPopup(message) {
      this.message = message;
      this.visible = true;
    },
    closePopup() {
      this.visible = false;
    },
  },
};
</script>
