<template>
  <div class="order-details-container">
    <div v-if="order" class="order-details-content">
      <div class="d-flex justify-content-between align-items-center">
        <h2>Bestelgegevens - ID: {{ order.id }}</h2>
        <button class="print-order-btn btn" @click="handlePrint">
          <i class="fas fa-print me-2"></i> Print Bestelling
        </button>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>Referentie</th>
            <th>Klant</th>
            <th>Datum</th>
            <th>Totaal</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ order.reference }}</td>
            <td>{{ order.customer || "Geen klantinformatie" }}</td>
            <td>{{ order.date }}</td>
            <td>€{{ order.total_tax_incl.toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>

      <h3>Gekochte artikelen</h3>
      <div v-if="order.lines && order.lines.length > 0">
        <table class="table">
          <thead>
            <tr>
              <th>Afbeelding</th>
              <th>Naam</th>
              <th>Referentie</th>
              <th>Aantal</th>
              <th>Prijs</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in order.lines" :key="item.product_id">
              <td>
                <img
                  :src="item.image_url"
                  alt="Product afbeelding"
                  style="max-width: 50px"
                />
              </td>
              <td>{{ item.name }}</td>
              <td>{{ item.reference }}</td>
              <td>{{ item.quantity }}</td>
              <td>€{{ item.price_tax_incl.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else>
        <p>Geen artikelen gevonden in deze bestelling.</p>
      </div>
      <button @click="goToOrderOverview" class="back-to-orders-btn btn mt-3">
        <i class="fas fa-arrow-left me-1"></i> Terug naar Bestellingen
      </button>
    </div>
    <div v-else>
      <p>Bezig met het laden van Bestelgegevens...</p>
    </div>

    <!-- Popup for Print Type -->
    <div v-if="showPopup" class="popup-overlay" @click="closePopup">
      <div class="popup-message" @click.stop>
        <h3>Kies het type document:</h3>
        <button @click="printDocument('INVOICE')">Factuur</button>
        <button @click="printDocument('RECEIPT')">Bon</button>
        <button @click="closePopup">Annuleren</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "OrderDetails",
  props: {
    id: {
      type: String,
      required: true,
    },
    apiBaseUrl: {
      type: String,
      required: true,
    },
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      order: null,
      showPopup: false, // Controls the visibility of the popup
    };
  },
  mounted() {
    this.fetchOrderDetails();
  },
  methods: {
    async fetchOrderDetails() {
      try {
        const response = await axios.get(
          `${this.apiBaseUrl}/orders/${this.id}`,
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
        this.order = response.data.data || null;
      } catch (error) {
        console.error("Fout bij het ophalen van orderdetails:", error);
        this.order = null;
      }
    },
    handlePrint() {
      this.showPopup = true;
    },
    closePopup() {
      this.showPopup = false;
    },
    async printDocument(printType) {
      if (!this.order || !this.order.id) {
        console.error("Fout: Geen order ID beschikbaar.");
        return;
      }

      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/orders/print-document`,
          {
            order_id: this.order.id,
            print_type: printType,
          },
          {
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
        console.log("Printen succesvol:", response.data);
        alert("Document succesvol geprint!");
        this.closePopup(); // Close the popup after printing
      } catch (error) {
        console.error("Fout bij het printen:", error.response?.data || error);
        alert("Er is een fout opgetreden bij het printen van het document.");
        this.closePopup(); // Close the popup on error
      }
    },
    goToOrderOverview() {
      this.$router.push({ name: "Bestellingen" });
    },
  },
};
</script>
