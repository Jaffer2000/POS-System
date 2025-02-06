<template>
  <div>
    <LoginComponent
      v-if="!token"
      :api-base-url="apiBaseUrl"
      :translations="translations"
      @login-success="setToken"
    />
    <div v-else>
      <HeaderComponent
        :api-base-url="apiBaseUrl"
        :translations="translations"
        :disable-focus="disableFocus"
        :token="token"
        :user="user"
        @product-scanned="addProductToOrder"
        @logout="logout"
      />
      <div class="container-fluid main-content">
        <div class="row">
          <div class="col-2">
            <SidebarComponent />
          </div>
          <div class="col-5" style="padding: 20px">
            <router-view
              :api-base-url="apiBaseUrl"
              :orderItems="orderItems"
              :translations="translations"
              :token="token"
              @focus-state-change="updateFocusState"
              @client-selected="handleClientSelection"
            />
          </div>
          <div class="col-5">
            <CashRegister
              :api-base-url="apiBaseUrl"
              :orderItems="orderItems"
              :translations="translations"
              :token="token"
              :selectedClient="selectedClient"
              @remove-item="removeProductFromOrder"
              @update-quantity="updateItemQuantity"
              @set-quantity="setItemQuantity"
              @clear-order-items="clearOrderItems"
              @clear-selected-client="selectedClient = null"
              @modal-state-change="updateModalState"
            />
          </div>
        </div>
      </div>
      <ErrorPopup ref="errorPopup" />
    </div>
  </div>
</template>

<script>
import LoginComponent from "./components/LoginComponent.vue";
import CashRegister from "./components/CashRegister.vue";
import HeaderComponent from "./components/HeaderComponent.vue";
import SidebarComponent from "./components/SidebarComponent.vue";
import ErrorPopup from "./components/ErrorPopup.vue";
import axios from "axios";

export default {
  components: {
    LoginComponent,
    CashRegister,
    HeaderComponent,
    SidebarComponent,
    ErrorPopup,
  },
  watch: {
    token(newToken) {
      if (newToken) {
        // If the user logs in, start tracking inactivity
        this.setupInactivityListener();
      } else {
        // If the user logs out, stop tracking inactivity
        this.cleanupInactivityListener();
      }
    },
  },
  props: {
    apiBaseUrl: {
      type: String,
      required: true,
    },
    translations: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      orderItems: [],
      token: null,
      user: null,
      disableFocus: false,
      inactivityTimeout: null,
      tokenExpirationTime: null, // Timestamp when the token will expire
      tokenExchangeTimeout: null,
      selectedClient: null,
    };
  },
  methods: {
    async fetchCurrentOrder() {
      try {
        const response = await axios.get(`${this.apiBaseUrl}/orders/current`, {
          headers: { Authorization: `Bearer ${this.token}` },
        });

        // Extract lines safely
        const cart = response.data?.data?.cart;
        if (cart && Array.isArray(cart.lines)) {
          this.orderItems = cart.lines.map((line) => ({
            product_id: line.product_id,
            refcode: line.reference,
            name: line.name,
            price_tax_incl: line.price_tax_incl,
            price_tax_excl: line.price_tax_excl,
            quantity: line.quantity,
            image_url: line.image_url,
          }));
        } else {
          console.warn("No cart or lines data available in the response.");
          this.orderItems = [];
        }
      } catch (error) {
        console.error("Error fetching current order:", error);
      }
    },
    async addProductToOrder(product) {
      // Find if the product is already in the order
      let existingItem = this.orderItems.find(
        (item) => item.refcode === product.refcode
      );

      // Check local stock before making the API call
      if (existingItem && existingItem.quantity + 1 > product.stock) {
        alert(`Cannot add more than available stock of ${product.stock}`);
        return; // Exit without updating if it exceeds stock
      } else if (!existingItem && product.stock < 1) {
        alert(this.translations.outOfStock);
        return;
      }

      // Make the API request to add the product to the order
      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/orders/add-product-to-order`,
          { refcode: product.refcode, quantity: 1 },
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          }
        );

        // Check response for OUT_OF_STOCK error
        if (
          response.data.status === "fail" &&
          response.data.data.code === "OUT_OF_STOCK"
        ) {
          const quantityAvailable = response.data.data.quantityAvailable;
          alert(
            `Only ${quantityAvailable} items are available in stock. Please adjust your quantity.`
          );

          // Adjust existing item quantity in UI to match available stock
          if (existingItem) {
            existingItem.quantity = quantityAvailable;
          }
          return; // Exit without adding further items
        }

        // If the response is successful, update orderItems in the UI
        if (existingItem) {
          existingItem.quantity += 1;
        } else {
          this.orderItems.push({
            ...product,
            quantity: 1,
          });
        }

        console.log("Updated order summary:", response.data);
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.$refs.errorPopup.showPopup(this.translations.outOfStock);
        } else {
          console.error("Error adding product to order:", error);
        }
      }
    },
    async removeProductFromOrder(refcode) {
      const previousItems = [...this.orderItems];
      this.orderItems = this.orderItems.filter(
        (item) => item.refcode !== refcode
      );

      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/orders/delete-product-from-order`,
          {
            refcode: refcode,
          },
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          }
        );

        if (response.data.status === "success") {
          console.log("Item successfully deleted from the order.");
        } else if (response.data.status === "fail") {
          this.handleError(response.data.data);
          this.orderItems = previousItems; // Restore previous items
        }
      } catch (error) {
        console.error("Error removing product from order:", error);
        this.orderItems = previousItems; // Restore previous items
      }
    },
    async updateItemQuantity({ refcode, quantity }) {
      const itemIndex = this.orderItems.findIndex(
        (item) => item.refcode === refcode
      );

      if (itemIndex !== -1) {
        const item = this.orderItems[itemIndex];
        const newQuantity = item.quantity + quantity;

        // If the quantity is zero, call removeProductFromOrder and exit
        if (newQuantity <= 0) {
          this.removeProductFromOrder(refcode); // Call remove method
          return; // Exit the function without further processing
        }

        // Check stock before updating
        if (newQuantity > item.stock) {
          alert(`Cannot update quantity. Available stock is ${item.stock}.`);
          return; // Exit without updating if it exceeds stock
        }

        // Make the API call to update quantity
        try {
          const response = await axios.post(
            `${this.apiBaseUrl}/orders/change-quantity`,
            { refcode: refcode, quantity: newQuantity },
            {
              headers: { Authorization: `Bearer ${this.token}` },
            }
          );

          // Check for OUT_OF_STOCK error in response
          if (
            response.data.status === "fail" &&
            response.data.data.code === "OUT_OF_STOCK"
          ) {
            const quantityAvailable = response.data.data.quantityAvailable;
            alert(
              `Only ${quantityAvailable} items are available in stock. Please adjust your quantity.`
            );

            // Adjust the item quantity if it exceeds available stock
            item.quantity = quantityAvailable;
            return; // Exit without adding further items
          }

          // If the response is successful, update the orderItems in the UI
          item.quantity = newQuantity;

          console.log("Updated order summary:", response.data);
        } catch (error) {
          if (error.response && error.response.status === 422) {
            this.$refs.errorPopup.showPopup(this.translations.outOfStock);
          } else {
            console.error("Error updating product quantity:", error);
          }
        }
      } else {
        console.error(`Item with refcode ${refcode} not found in the order.`);
      }
    },
    async setItemQuantity({ refcode, setQuantity }) {
      const itemIndex = this.orderItems.findIndex(
        (item) => item.refcode === refcode
      );

      if (itemIndex !== -1) {
        const item = this.orderItems[itemIndex];

        // Check if the requested quantity exceeds stock
        if (setQuantity > item.stock) {
          this.$refs.errorPopup.showPopup(
            `Requested quantity of ${setQuantity} exceeds available stock of ${item.stock}.`
          );
          return; // Prevent further processing if quantity exceeds stock
        }

        // API call to update quantity on the server
        try {
          const response = await axios.post(
            `${this.apiBaseUrl}/orders/change-quantity`,
            { refcode: refcode, quantity: setQuantity },
            {
              headers: { Authorization: `Bearer ${this.token}` },
            }
          );

          // Handle stock errors from the API response
          if (
            response.data.status === "fail" &&
            response.data.data.code === "OUT_OF_STOCK"
          ) {
            const quantityAvailable = response.data.data.quantityAvailable;
            this.$refs.errorPopup.showPopup(
              `Only ${quantityAvailable} items are available. Adjusting quantity to available stock.`
            );
            item.quantity = quantityAvailable; // Set quantity to available stock
            return; // Exit if out of stock
          }

          // If the response is successful, update the orderItems in the UI
          item.quantity = setQuantity;
          console.log("Quantity successfully updated:", response.data);
        } catch (error) {
          if (error.response && error.response.status === 422) {
            this.$refs.errorPopup.showPopup(this.translations.outOfStock);
          } else {
            console.error("Error updating quantity:", error);
          }
        }
      } else {
        console.error(`Item with refcode ${refcode} not found in the order.`);
      }
    },
    clearOrderItems() {
      this.orderItems = [];
    },
    updateModalState(state) {
      this.disableFocus = state; // Disable focus when modal is shown
    },
    updateFocusState(isFocused) {
      this.disableFocus = isFocused;
    },
    handleClientSelection(client) {
      this.selectedClient = client;
    },
    logout() {
      // Remove the token and workstation from localStorage
      this.clearTokenData();
    },
    resetInactivityTimer() {
      // Clear the existing timeout
      if (this.inactivityTimeout) {
        clearTimeout(this.inactivityTimeout);
      }

      // Set a new timeout for 15 minutes
      this.inactivityTimeout = setTimeout(() => {
        this.logout();
      }, 15 * 60 * 1000); // 15 minutes in milliseconds
    },
    setupInactivityListener() {
      // Events that reset the inactivity timer
      const events = ["mousemove", "keydown", "click", "scroll"];

      // Add listeners to reset the timer on user interaction
      events.forEach((event) => {
        window.addEventListener(event, this.resetInactivityTimer);
      });

      // Start the initial inactivity timer
      this.resetInactivityTimer();
    },
    cleanupInactivityListener() {
      // Clear the timeout
      if (this.inactivityTimeout) {
        clearTimeout(this.inactivityTimeout);
      }

      // Remove event listeners
      const events = ["mousemove", "keydown", "click", "scroll"];
      events.forEach((event) => {
        window.removeEventListener(event, this.resetInactivityTimer);
      });
    },
    async setToken(token, isExchange = false) {
      try {
        const response = await axios.get(`${this.apiBaseUrl}/token`, {
          headers: { Authorization: `Bearer ${token}` },
        });

        if (response.data.status === "success") {
          const { firstname, lastname, token: tokenData } = response.data.data;
          const { expiresIn } = tokenData; // Extract expiresIn from the token object

          this.token = token;
          this.user = { firstName: firstname, lastName: lastname };

          console.log("ExpiresIn received:", expiresIn);
          console.log("Full API response:", response.data);

          // Calculate and store the token expiration time
          this.tokenExpirationTime = Date.now() + expiresIn * 1000;
          localStorage.setItem("authToken", token);
          localStorage.setItem("userFirstName", firstname);
          localStorage.setItem("userLastName", lastname);
          localStorage.setItem("tokenExpirationTime", this.tokenExpirationTime);

          // Schedule token exchange if it's not during an exchange call
          if (!isExchange) {
            this.scheduleTokenExchange(expiresIn);
          }
          return true;
        }
      } catch (error) {
        console.error("Error validating token:", error);
      }

      this.clearTokenData();
      return false;
    },
    scheduleTokenExchange(expiresIn) {
      if (!expiresIn || isNaN(expiresIn)) {
        console.error(
          "Invalid expiresIn value. Cannot schedule token exchange."
        );
        return;
      }

      const exchangeInMs = (expiresIn - 300) * 1000;

      if (exchangeInMs <= 0) {
        console.warn("Token has less than 60 seconds. Exchanging immediately.");
        this.exchangeToken();
        return;
      }

      if (this.tokenExchangeTimeout) {
        clearTimeout(this.tokenExchangeTimeout);
      }

      this.tokenExchangeTimeout = setTimeout(() => {
        this.exchangeToken();
      }, exchangeInMs);

      console.log(
        `Token exchange scheduled in ${(exchangeInMs / 1000).toFixed(
          1
        )} seconds.`
      );
    },
    async exchangeToken() {
      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/token/exchange`,
          {},
          {
            headers: { Authorization: `Bearer ${this.token}` },
          }
        );

        if (response.status === 200) {
          const newToken = response.data.data.token.value;
          console.log("Token exchanged successfully.");
          await this.setToken(newToken, true); // Pass true to avoid rescheduling
        } else {
          console.warn("Token exchange failed. Logging out.");
          this.logout();
        }
      } catch (error) {
        console.error("Error exchanging token:", error);
        this.logout();
      }
    },
    clearTokenData() {
      this.token = null;
      this.user = null;
      this.tokenExpirationTime = null;

      localStorage.removeItem("authToken");
      localStorage.removeItem("userFirstName");
      localStorage.removeItem("userLastName");
      localStorage.removeItem("tokenExpirationTime");

      if (this.tokenExchangeTimeout) clearTimeout(this.tokenExchangeTimeout);
    },
    handleError(errorData) {
      switch (errorData.code) {
        case "NOT_FOUND":
          console.error("Product not found:", errorData.message);
          break;
        case "BAD_REQUEST":
          console.error("Bad request:", errorData.message);
          break;
        case "OUT_OF_STOCK":
          console.error("Out of stock:", errorData.message);
          alert("This product is currently out of stock.");
          break;
        case "MINIMAL_QUANTITY_REQUIRED":
          console.error("Minimum quantity required:", errorData.message);
          break;
        default:
          console.error("An unknown error occurred.");
      }
    },
  },
  mounted() {
    const storedToken = localStorage.getItem("authToken");
    const storedExpirationTime = parseInt(
      localStorage.getItem("tokenExpirationTime"),
      10
    );

    if (storedToken && storedExpirationTime > Date.now()) {
      const expiresIn = (storedExpirationTime - Date.now()) / 1000;

      this.setToken(storedToken).then((isValid) => {
        if (isValid) {
          this.scheduleTokenExchange(expiresIn);
          this.fetchCurrentOrder(); // Fetch order only if token is valid
        } else {
          console.warn("Stored token is invalid. Logging out.");
          this.logout();
        }
      });
    } else {
      console.warn("No valid token found. Redirecting to login.");
      this.logout();
    }
    if (this.token) {
      this.fetchCurrentOrder();
      this.setupInactivityListener();
    }
  },
  beforeUnmount() {
    if (this.tokenExchangeTimeout) {
      clearTimeout(this.tokenExchangeTimeout); // Clean up the timer
    }
    this.cleanupInactivityListener();
  },
};
</script>
