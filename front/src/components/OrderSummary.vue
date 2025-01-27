<template>
  <div class="order-summary">
    <div class="space" style="height: 50px"></div>
    <div ref="orderList" class="order-items-list">
      <div
        v-for="item in orderItems"
        :key="item.refcode"
        :class="[
          'order-item',
          {
            selected:
              selectedProduct && selectedProduct.refcode === item.refcode,
          },
        ]"
        @click="selectProduct(item)"
      >
        <img :src="item.image_url" :alt="item.name" class="product-image" />

        <div class="item-details">
          <div class="item-header">
            <span class="product-name">{{ item.name }}</span>
            <span class="product-price">
              €{{
                item.price_tax_incl ? item.price_tax_incl.toFixed(2) : "0.00"
              }}
              <span style="font-weight: normal">(incl. btw)</span>
            </span>

            <span class="product-price1"
              >€{{ item.price_tax_excl.toFixed(2) }}
              <span style="font-weight: normal">(excl. btw)</span></span
            >
          </div>

          <div class="quantity-controls">
            <button
              @click="
                $emit('update-quantity', {
                  refcode: item.refcode,
                  quantity: -1,
                })
              "
              class="quantity-button"
            >
              <i class="fa-solid fa-minus"></i>
            </button>
            <div class="quantity-wrapper">
              <input
                v-if="
                  quantityEditable &&
                  selectedProduct &&
                  selectedProduct.refcode === item.refcode
                "
                v-model="editableQuantity"
                type="number"
                class="editable-quantity"
                @click.stop
              />
              <button
                v-if="
                  quantityEditable &&
                  selectedProduct &&
                  selectedProduct.refcode === item.refcode
                "
                @click="confirmQuantity"
                class="checkmark-button"
              >
                <i class="fa-solid fa-check"></i>
              </button>
              <div v-else class="quantity-counter">{{ item.quantity }}</div>
            </div>
            <button
              @click="
                $emit('update-quantity', { refcode: item.refcode, quantity: 1 })
              "
              class="quantity-button"
            >
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>
        </div>
        <button @click="removeItem(item.refcode)" class="delete-button">
          <i class="fa-solid fa-trash-can"></i>
        </button>
      </div>
      <div class="scroll-spacer"></div>
    </div>

    <div class="bottom-section">
      <div class="keyboard">
        <div class="keyboard-row">
          <button @click="setQuantityMode">Aant.</button>
          <button @click="handleKeyPress(1)">1</button>
          <button @click="handleKeyPress(2)">2</button>
          <button @click="handleKeyPress(3)">3</button>
        </div>
        <div class="keyboard-row">
          <button @click="editFlatDiscount">Prijs</button>
          <button @click="handleKeyPress(4)">4</button>
          <button @click="handleKeyPress(5)">5</button>
          <button @click="handleKeyPress(6)">6</button>
        </div>
        <div class="keyboard-row">
          <button @click="editDiscount">%</button>
          <button @click="handleKeyPress(7)">7</button>
          <button @click="handleKeyPress(8)">8</button>
          <button @click="handleKeyPress(9)">9</button>
        </div>
        <div class="keyboard-row">
          <button @click="handleKeyPress('delete')">Del</button>
          <button>+/-</button>
          <button @click="handleKeyPress(0)">0</button>
          <button @click="handleKeyPress('.')">.</button>
        </div>
        <button class="customer-button">
          <div class="user-icon">
            <i class="fas fa-user"></i>
          </div>
          Klant
        </button>
      </div>

      <div class="summary-of-costs">
        <div class="cost-item">
          Subtotaal <span class="price">€{{ subtotal.toFixed(2) }}</span>
        </div>
        <div class="cost-item">
          Korting
          <span v-if="!discountEditable && !flatDiscountEditable" class="price">
            €{{ discount.toFixed(2) }}
          </span>

          <!-- Percentage Discount Input -->
          <div
            class="discount-input-wrapper"
            :class="{ hidden: !discountEditable }"
          >
            <input
              v-model="discountPercentage"
              type="text"
              class="discount-input"
              min="0"
              max="100"
              step="1"
            />
            <button
              @click="confirmDiscount('percentage')"
              class="checkmark-button"
            >
              <i class="fa-solid fa-check"></i>
            </button>
          </div>

          <!-- Flat Amount Discount Input -->
          <div
            class="discount-input-wrapper"
            :class="{ hidden: !flatDiscountEditable }"
          >
            <input
              v-model="flatDiscountAmount"
              type="text"
              class="discount-input"
              min="0"
              step="0.01"
            />
            <button @click="confirmDiscount('amount')" class="checkmark-button">
              <i class="fa-solid fa-check"></i>
            </button>
          </div>
        </div>
        <div class="cost-item">
          Btw <span class="price">€{{ vat.toFixed(2) }}</span>
        </div>
        <div class="cost-item-total">
          Totaalprijs
          <span class="total-price">€{{ totalPrice.toFixed(2) }}</span>
        </div>
        <button class="checkout-button" @click="openModal">
          Doorgaan naar afrekenen
        </button>
      </div>
    </div>
    <div v-if="showPopup" class="popup-overlay">
      <div class="popup-message">
        <p>
          {{ this.translations.invalidDiscount }}
        </p>
        <button @click="closePopup">Sluiten</button>
      </div>
    </div>
    <!-- Checkout Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <h2>Betalen</h2>

        <div class="order-items-scrollable">
          <div
            v-for="item in orderItems"
            :key="item.refcode"
            class="modal-order-item"
          >
            <div class="item-info">
              <span class="product-name1">{{ item.name }}</span>
              <span class="product-quantity">x{{ item.quantity }}</span>
              <span class="product-pricee"
                >€{{ item.price_tax_incl.toFixed(2) }}</span
              >
            </div>
          </div>
        </div>

        <p class="total-price1">Totaalprijs: €{{ totalPrice.toFixed(2) }}</p>

        <div class="payment-methods">
          <h3>Selecteer Betaalmethode</h3>
          <label>
            <input type="radio" v-model="selectedPaymentMethod" value="cash" />
            Contant
          </label>
          <label>
            <input type="radio" v-model="selectedPaymentMethod" value="card" />
            Kaart
          </label>

          <div v-if="selectedPaymentMethod === 'cash'" class="cash-fields">
            <input
              type="number"
              v-model.number="cashReceived"
              class="received-input"
              placeholder="Ontvangen bedrag"
            />
            <input
              type="text"
              class="change-input"
              :value="calculateChange"
              placeholder="Wisselgeld"
              readonly
            />
          </div>

          <p v-if="showPaymentWarning" class="payment-warning">
            {{ this.translations.paymentWarning }}
          </p>
        </div>

        <button @click="confirmCheckout" class="confirm-checkout-modal">
          Bevestig Betaling
        </button>
        <button @click="closeModal" class="close-modal">Sluiten</button>
      </div>
    </div>
    <!-- Confirmation Popup -->
    <div v-if="showPaymentConfirmation" class="confirmation-popup-overlay">
      <div class="confirmation-popup">
        <p>{{ this.translations.paymentConfirmation }}</p>
        <button @click="handlePaymentConfirmation(true)">Ja</button>
        <button @click="handlePaymentConfirmation(false)">Nee</button>
      </div>
    </div>
    <!-- Receipt Popup -->
    <div v-if="showReceiptPopup" class="confirmation-popup-overlay">
      <div class="confirmation-popup">
        <p>{{ this.translations.receiptConfirmation }}</p>
        <button @click="handleReceiptSelection(true)">Ja</button>
        <button @click="handleReceiptSelection(false)">Nee</button>
      </div>
    </div>
    <div id="receipt-content" style="display: none">
      <h2>Receipt</h2>
      <p>Total: €{{ totalPrice.toFixed(2) }}</p>
      <div
        v-for="item in orderItems"
        :key="item.refcode"
        class="modal-order-item"
      >
        <div class="item-info">
          <span class="product-name1">{{ item.name }}</span>
          <span class="product-quantity">x{{ item.quantity }}</span>
          <span class="product-pricee"
            >€{{ item.price_tax_incl.toFixed(2) }}</span
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      previousItemCount: 0,
      selectedProduct: null,
      quantityMode: false,
      quantityEditable: false,
      editableQuantity: 0,
      discountEditable: false,
      flatDiscountEditable: false,
      discountPercentage: 0,
      flatDiscountAmount: 0,
      previousDiscountPercentage: 0,
      previousFlatDiscountAmount: 0,
      newTotal: 0,
      cashReceived: 0,
      showPopup: false,
      showModal: false,
      selectedPaymentMethod: null,
      showPaymentWarning: false,
      showPaymentConfirmation: false,
      showReceiptPopup: false,
    };
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
  watch: {
    orderItems: {
      handler(newItems) {
        // Scroll to the bottom only when an item is added
        if (newItems.length > this.previousItemCount) {
          this.scrollToBottom();
        }
        // Update previous item count for future comparisons
        this.previousItemCount = newItems.length;
      },
      deep: true,
    },
    selectedPaymentMethod(newValue) {
      if (newValue === "cash") {
        this.showPaymentWarning = false; // Hide the warning when "Contant" is selected
      }
    },
    showModal(newValue) {
      // Emit modal-state-change based on showModal value
      this.$emit("modal-state-change", newValue);
    },
  },
  computed: {
    totalPrice() {
      const discountedSubtotal = this.subtotal - this.discount;
      return discountedSubtotal + this.vat;
    },
    subtotal() {
      return this.orderItems.reduce(
        (total, item) =>
          total + (parseFloat(item.price_tax_excl) || 0) * item.quantity,
        0
      );
    },
    vat() {
      const totalTaxable = this.orderItems.reduce(
        (total, item) =>
          total +
          (parseFloat(item.price_tax_incl) - parseFloat(item.price_tax_excl)) *
            item.quantity,
        0
      );
      return totalTaxable; // Calculated VAT based on the taxable amount
    },
    discount() {
      if (this.flatDiscountAmount > 0) {
        // Use flat discount if set
        return this.flatDiscountAmount;
      } else if (this.discountPercentage > 0) {
        const totalBeforeDiscount = this.subtotal + this.vat; // Calculate discount based on the total price (subtotal + vat)
        return (this.discountPercentage / 100) * totalBeforeDiscount;
      }
      return 0; // No discount
    },
    calculateChange() {
      return (this.cashReceived - this.totalPrice).toFixed(2);
    },
  },
  methods: {
    removeItem(refcode) {
      this.$emit("remove-item", refcode);
    },
    selectProduct(product) {
      this.selectedProduct = product;
      this.editableQuantity = product.quantity;
    },
    setQuantityMode() {
      this.quantityEditable = true;
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const orderList = this.$refs.orderList;
        if (orderList) {
          orderList.scrollTop = orderList.scrollHeight;
        }
      });
    },
    handleKeyPress(key) {
      if (this.selectedProduct && this.quantityEditable) {
        // Handle quantity input
        this.handleInput("editableQuantity", key);
      } else if (this.discountEditable) {
        // Handle percentage discount input
        this.handleInput("discountPercentage", key);
      } else if (this.flatDiscountEditable) {
        // Handle flat discount input
        this.handleInput("flatDiscountAmount", key);
      }
    },

    handleInput(field, key) {
      const currentValue = this[field]?.toString() || "0";

      if (!isNaN(key)) {
        // Append numeric key
        this[field] =
          currentValue === "0" ? key.toString() : currentValue + key.toString();
      } else if (key === "delete") {
        // Remove the last character
        this[field] = currentValue.slice(0, -1) || "0";
      } else if (key === ".") {
        // Append a dot if not already present
        if (!currentValue.includes(".")) {
          this[field] = currentValue + ".";
        }
      } else if (key === "enter") {
        // Convert the input to a number when confirmed
        this[field] = parseFloat(this[field]) || 0;
      }
    },
    confirmQuantity() {
      const newQuantity = parseInt(this.editableQuantity, 10);
      if (isNaN(newQuantity) || this.editableQuantity === "") {
        this.editableQuantity = this.selectedProduct.quantity; // Reset editable quantity to the original
        this.quantityEditable = false;
        return;
      }

      if (newQuantity === 0) {
        this.$emit("remove-item", this.selectedProduct.refcode);
      } else {
        this.$emit("set-quantity", {
          refcode: this.selectedProduct.refcode,
          setQuantity: newQuantity,
        });
      }
      this.quantityEditable = false;
    },
    handleOutsideClick(event) {
      if (this.quantityEditable && !this.$el.contains(event.target)) {
        this.confirmQuantity();
      }
    },
    editDiscount() {
      this.discountEditable = true;
      this.flatDiscountEditable = false; // Ensure flat discount is not active
    },
    editFlatDiscount() {
      this.flatDiscountEditable = true;
      this.discountEditable = false; // Ensure percentage discount is not active
    },
    startEditingDiscount() {
      this.previousDiscountPercentage = this.discountPercentage; // Save the current value
      this.discountEditable = true; // Allow editing
    },
    confirmDiscount(type) {
      if (type === "percentage") {
        if (this.discountPercentage < 0 || this.discountPercentage > 100) {
          // Display the popup for invalid percentage
          this.showPopup = true;
          return;
        }
        this.flatDiscountAmount = 0; // Clear flat discount
        this.discountEditable = false;
      } else if (type === "amount") {
        if (
          this.flatDiscountAmount < 0 ||
          this.flatDiscountAmount > this.subtotal + this.vat
        ) {
          // Display the popup for invalid amount
          this.showPopup = true;
          return;
        }

        this.discountPercentage = 0; // Clear percentage discount
        this.flatDiscountEditable = false;
      }

      // Ensure the values are numbers
      this.discountPercentage = parseFloat(this.discountPercentage) || 0;
      this.flatDiscountAmount = parseFloat(this.flatDiscountAmount) || 0;

      // Optionally send data to the server
      const discountPayload = {
        discount_type: type,
        value:
          type === "percentage"
            ? this.discountPercentage
            : this.flatDiscountAmount,
      };

      fetch(`${this.apiBaseUrl}/orders/apply-discount`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${this.token}`,
        },
        body: JSON.stringify(discountPayload),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          if (data && data.new_total) {
            this.newTotal = data.new_total;
          }
        })
        .catch((error) => {
          console.error("Error applying discount:", error);
        });
    },
    // Method to close the popup
    closePopup() {
      this.showPopup = false; // Hide the popup
      this.discountPercentage = this.previousDiscountPercentage || 0;
      this.flatDiscountAmount = this.previousFlatDiscountAmount || 0;
    },
    openModal() {
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false; // Hide modal
      this.showPaymentWarning = false;
      this.selectedPaymentMethod = null;
    },
    async confirmCheckout() {
      if (!this.selectedPaymentMethod) {
        this.showPaymentWarning = true;
        return;
      }

      // Show confirmation popup after payment attempt
      this.showPaymentConfirmation = true;
      this.showPaymentWarning = false;
    },
    async createNewOrder() {
      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/orders/new`,
          {},
          {
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
        this.handleNewOrderResponse(response.data);
      } catch (error) {
        console.error("Error creating new order:", error);
      }
    },
    async cancelOrder() {
      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/orders/cancel`,
          {},
          {
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
        this.handleCancelOrderResponse(response.data);
      } catch (error) {
        console.error("Error canceling order:", error);
      }
    },
    async cancelPayment() {
      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/payment/cancel`,
          {},
          {
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
        this.handleCancelOrderResponse(response.data);
      } catch (error) {
        console.error("Error canceling payment:", error);
      }
    },
    async handlePaymentConfirmation(success) {
      if (success) {
        // Proceed with successful payment handling
        console.log("Payment completed successfully.");

        // Determine payment method for the API
        const amount = this.totalPrice.toFixed(2);
        const paymentMethod =
          this.selectedPaymentMethod === "cash"
            ? "CASH"
            : "CREDIT_CARD_OFFLINE";

        try {
          // Step 1: Call the checkout endpoint
          const checkoutResponse = await axios.post(
            `${this.apiBaseUrl}/checkout`,
            {
              amount: amount,
              paymentMethod: paymentMethod,
            },
            {
              headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${this.token}`,
              },
            }
          );

          // Handle the checkout response
          this.handleCheckoutResponse(checkoutResponse.data);

          // Step 2: Depending on the selected payment method, call the respective payment endpoint
          let paymentResponse;

          if (this.selectedPaymentMethod === "cash") {
            paymentResponse = await axios.post(
              `${this.apiBaseUrl}/payment/cash`,
              { amount, paymentMethod },
              {
                headers: {
                  "Content-Type": "application/json",
                  Authorization: `Bearer ${this.token}`,
                },
              }
            );
            // Handle successful cash payment response
            this.handleCashPaymentResponse(paymentResponse.data);
          } else if (this.selectedPaymentMethod === "card") {
            paymentResponse = await axios.post(
              `${this.apiBaseUrl}/payment/card`,
              { amount, paymentMethod },
              {
                headers: {
                  "Content-Type": "application/json",
                  Authorization: `Bearer ${this.token}`,
                },
              }
            );
            // Handle successful card payment response
            this.handleCardPaymentResponse(paymentResponse.data);
          }
        } catch (error) {
          if (error.response && error.response.status === 400) {
            console.log("Checkout failed:", error.response.data);
          } else {
            console.error("An unexpected error occurred:", error);
          }
        }

        // Reset states and close popups
        this.showPaymentConfirmation = false;
        this.selectedPaymentMethod = null;
        this.showReceiptPopup = true;
      } else {
        // Close the confirmation popup without proceeding
        this.showPaymentConfirmation = false;
      }
    },
    async handleReceiptSelection(wantsReceipt) {
      this.showReceiptPopup = false;

      // If the user wants a receipt, print it first
      if (wantsReceipt) {
        this.showModal = false;
        this.printReceipt(); // Print receipt first
        console.log("User wants a receipt.");
      } else {
        this.showModal = false;
        console.log("User does not want a receipt.");
      }

      // Wait for createNewOrder to complete
      await this.createNewOrder();

      // Reset the discount input to 0
      this.discountPercentage = 0;
      this.flatDiscountAmount = 0;

      // Delay clearing the UI state for orderItems by 3 seconds
      setTimeout(() => {
        this.$emit("clear-order-items");
      }, 3000);
    },
    async printReceipt() {
      try {
        // Retrieve all orders with sorting by date (most recent first)
        const ordersResponse = await axios.get(
          `${this.apiBaseUrl}/orders?sort=date_desc&page=1&per_page=1`, // Assuming API supports sorting by date and pagination
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          }
        );

        // Ensure we get a valid order from the response
        const ordersData = ordersResponse.data.data.list;
        if (ordersData.length === 0) {
          alert("No orders found.");
          return;
        }

        // Get the most recent order (the first in the sorted list)
        const order = ordersData[0];

        // Log the order ID of the most recent order
        console.log("Order ID for printing:", order.id);

        // Call the print document endpoint directly with the most recent order ID
        const printResponse = await axios.post(
          `${this.apiBaseUrl}/orders/print-document`,
          {
            order_id: order.id, // Pass the most recent order ID here
            print_type: "RECEIPT", // Print type is always 'RECEIPT'
          },
          {
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
        this.handlePrintReceiptResponse(printResponse.data);
      } catch (error) {
        console.error("Error printing receipt:", error);
      }
    },
    handleCheckoutResponse(response) {
      if (response.status === "success") {
        // Proceed to payment confirmation step
        this.showPaymentConfirmation = true;
      } else if (response.status === "fail") {
        // Handle failure scenarios, e.g., show an error message
        console.error("Checkout failed:", response.data.message);
      }
    },
  },
  mounted() {
    document.addEventListener("click", this.handleOutsideClick);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleOutsideClick);
  },
};
</script>
