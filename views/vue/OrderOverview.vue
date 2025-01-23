<template>
  <div class="order-overview-container">
    <h2>Bestellingen</h2>

    <!-- Search Bar -->
    <div class="search-bar-order-overview mb-3">
      <div class="input-group">
        <input
          type="text"
          class="form-control"
          v-model="searchQuery"
          placeholder="Zoek op ID, Referentie, Klant of Datum"
          @focus="$emit('focus-state-change', true)"
          @blur="$emit('focus-state-change', false)"
          @input="onSearchInput"
        />
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
      </div>
    </div>

    <div v-if="filteredOrders.length === 0">
      <p>Geen bestellingen gevonden.</p>
    </div>
    <div v-else>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>#</th>
            <th>Referentie</th>
            <th>Klant</th>
            <th>Betaling</th>
            <th>Datum</th>
            <th>Totaal</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in paginatedOrders.filter((order) => order)"
            :key="order.id"
            @click="
              $router.push({ name: 'OrderDetails', params: { id: order.id } })
            "
            style="cursor: pointer"
          >
            <td>{{ order.id }}</td>
            <td>{{ order.reference }}</td>
            <td>{{ order.customer }}</td>
            <td>{{ order.payment_method }}</td>
            <td>{{ order.date }}</td>
            <td>€{{ (order.total_tax_incl || 0).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination Controls -->
      <nav>
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <button class="page-link" @click="goToPage(currentPage - 1)">
              Vorige
            </button>
          </li>

          <!-- Dynamically display pages -->
          <li
            v-for="page in pageRange"
            :key="page"
            class="page-item"
            :class="{ active: page === currentPage }"
          >
            <button class="page-link" @click="goToPage(page)">
              {{ page }}
            </button>
          </li>

          <li
            class="page-item"
            :class="{ disabled: currentPage === totalPages }"
          >
            <button class="page-link" @click="goToPage(currentPage + 1)">
              Volgende
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "OrderOverview",
  props: {
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
  data() {
    return {
      orders: [],
      searchQuery: "",
      currentPage: 1,
      ordersPerPage: 8, // Display 8 orders per page
      totalItems: 0, // To track the total number of items
    };
  },
  computed: {
    filteredOrders() {
      // Only filter locally if there's no API-based search
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        return this.orders.filter((order) => {
          return (
            order.id.toString().includes(query) ||
            order.reference.toLowerCase().includes(query) ||
            order.customer.toLowerCase().includes(query) ||
            order.date.toLowerCase().includes(query)
          );
        });
      }
      return this.orders; // Use the API-provided paginated results if no local search
    },
    totalPages() {
      return Math.ceil(this.totalItems / this.ordersPerPage);
    },
    pageRange() {
      let range = [];
      const maxPagesToShow = 10;
      const startPage = Math.max(1, this.currentPage - 4);
      const endPage = Math.min(this.totalPages, startPage + maxPagesToShow - 1);

      for (let page = startPage; page <= endPage; page++) {
        range.push(page);
      }

      return range;
    },
    paginatedOrders() {
      return this.filteredOrders; // Directly return filtered orders
    },
  },
  mounted() {
    this.fetchOrders(); // Fetch orders on component mount
  },
  methods: {
    async fetchOrders() {
      try {
        const response = await axios.get(`${this.apiBaseUrl}/orders`, {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
          params: {
            searchterm: this.searchQuery,
            page: this.currentPage,
            per_page: this.ordersPerPage,
          },
        });

        console.log("API Response:", response.data);
        this.orders = response.data.data.list; // API paginated data
        this.totalItems = response.data.data.pagination.total_items;

        console.log("Orders for Page:", this.currentPage, this.orders);
      } catch (error) {
        console.error("Fout bij het ophalen van bestellingen:", error);
      }
    },
    onSearchInput() {
      // Reset current page to 1 when search is updated
      this.currentPage = 1;
      this.fetchOrders(); // Fetch orders again on search change
    },
    goToPage(page) {
      if (page > 0 && page <= this.totalPages) {
        this.currentPage = page;
        this.fetchOrders(); // Fetch orders again when page is changed
      }
    },
  },
};
</script>
