<template>
  <header>
    <div class="header-content">
      <span class="header-title">Point of Sale</span>
      <div class="search-bar">
        <i class="fas fa-magnifying-glass search-icon"></i>
        <input
          ref="searchInput"
          type="text"
          v-model="barcodeInput"
          @input="debouncedOnBarcodeInput"
          id="barcodeInput"
          placeholder="Zoek Product op Naam, Barcode, Product id"
        />
        <i class="fas fa-barcode barcode-icon"></i>
      </div>

      <div class="user-info">
        <i class="fas fa-user user-image"></i>
        <span>{{
          user ? `${user.firstName} ${user.lastName}` : "John Doe"
        }}</span>
      </div>

      <div class="user-signoff">
        <i class="fas fa-right-from-bracket user-signofficon"></i>
        <span @click="handleLogout">Afmelden</span>
      </div>
    </div>

    <!-- Popup for unknown barcode -->
    <div v-if="showPopup" class="popup-overlay">
      <div class="popup-message">
        <p>{{ this.translations.barcodeNotFound }}</p>
        <button @click="closePopup">Sluiten</button>
      </div>
    </div>
  </header>
</template>

<script>
import axios from "axios";

export default {
  name: "HeaderComponent",
  props: {
    apiBaseUrl: {
      type: String,
      required: true,
    },
    translations: {
      type: Object,
      required: true,
    },
    disableFocus: {
      type: Boolean,
      default: false,
    },
    token: {
      type: String,
      required: true,
    },
    user: {
      type: Object,
      default: () => null, // Default to null if no user is provided
    },
  },
  data() {
    return {
      barcodeInput: "",
      showPopup: false,
      debouncedOnBarcodeInput: null,
      focusInterval: null,
    };
  },
  watch: {
    disableFocus(newValue) {
      if (newValue) {
        clearInterval(this.focusInterval); // Stop focusing
      } else {
        this.focusInterval = setInterval(this.maintainFocus, 100); // Resume focusing
      }
    },
  },
  methods: {
    debounce(func, wait) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    },
    async fetchProduct(term, type = "BARCODE") {
      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/products/find`,
          {
            type,
            term,
          },
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          }
        );

        const jsend = response.data;

        if (jsend.status === "success" && jsend.data.length > 0) {
          return jsend.data; // Return the list of products
        } else {
          return null; // No products found
        }
      } catch (error) {
        console.error("Error fetching product:", error);
        this.showPopup = true; // Show popup for fetch error
        return null; // Return null if an error occurs
      }
    },
    async onBarcodeInput() {
      const term = this.barcodeInput.trim();
      if (!term) return;

      this.showPopup = false; // Hide any existing popups

      // Determine the type of input
      let type;
      if (/^\d+$/.test(term)) {
        type = term.length === 12 || term.length === 13 ? "BARCODE" : "ALL";
      } else if (term.match(/^[a-zA-Z0-9-]+$/)) {
        type = "REFERENCE";
      } else {
        type = "NAME";
      }

      // Fetch product based on detected type
      const products = await this.fetchProduct(term, type);

      if (products && products.length > 0) {
        const product = products[0]; // Handle multiple products (select first for now)
        this.$emit("product-scanned", product);
        this.barcodeInput = ""; // Clear input
      } else {
        this.showPopup = true; // Show popup if no product found
      }
    },
    closePopup() {
      this.showPopup = false;
      this.barcodeInput = "";
    },
    maintainFocus() {
      if (!this.disableFocus) {
        this.$refs.searchInput.focus();
      }
    },
    handleLogout() {
      this.$emit("logout"); // Emit the logout event to App.vue
    },
  },
  mounted() {
    this.focusInterval = setInterval(this.maintainFocus, 100);
    this.debouncedOnBarcodeInput = this.debounce(this.onBarcodeInput, 100);
  },
  beforeUnmount() {
    clearInterval(this.focusInterval);
  },
};
</script>
