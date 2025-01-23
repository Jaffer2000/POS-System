<template>
  <div>
    <OrderSummary
      :api-base-url="apiBaseUrl"
      :orderItems="orderItems"
      :translations="translations"
      :token="token"
      @remove-item="removeProductFromOrder"
      @update-quantity="updateItemQuantity"
      @set-quantity="setItemQuantity"
      @clear-order-items="forwardClearOrderItems"
      @modal-state-change="forwardModalStateChange"
    />
  </div>
</template>

<script>
import OrderSummary from "./OrderSummary.vue";

export default {
  components: {
    OrderSummary,
  },
  props: {
    orderItems: {
      type: Array,
      required: true,
    },
    apiBaseUrl: {
      type: String,
      required: true,
    },
    translations: {
      type: Object,
      required: true,
    },
    token: {
      type: String,
      required: true,
    },
  },
  methods: {
    removeProductFromOrder(refcode) {
      this.$emit("remove-item", refcode);
    },
    updateItemQuantity(payload) {
      this.$emit("update-quantity", payload);
    },
    setItemQuantity(payload) {
      this.$emit("set-quantity", payload);
    },
    forwardClearOrderItems() {
      this.$emit("clear-order-items");
    },
    forwardModalStateChange(state) {
      this.$emit("modal-state-change", state);
    },
  },
};
</script>
