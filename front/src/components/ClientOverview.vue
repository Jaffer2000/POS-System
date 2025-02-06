<template>
  <div class="client-overview">
    <h2 class="client-title">Klanten</h2>
    <div class="mb-3">
      <div class="input-group">
        <input
          type="text"
          class="form-control"
          v-model="searchQuery"
          placeholder="Zoek op Naam, Emailadres of Adres"
          @focus="$emit('focus-state-change', true)"
          @blur="$emit('focus-state-change', false)"
          @input="onSearchInput"
        />
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
      </div>
    </div>
    <div v-if="filteredClients.length === 0">
      <p>Geen klanten gevonden.</p>
    </div>
    <ul class="client-list">
      <li
        v-for="client in clients"
        :key="client.id"
        :class="{ 'client-item': true, expanded: expandedClient === client.id }"
        @click="toggleDetails(client, client.id)"
      >
        <p class="client-name">{{ client.firstname }} {{ client.lastname }}</p>
        <p class="client-email">{{ client.email }}</p>
        <div v-if="expandedClient === client.id" class="client-details">
          <div v-for="(address, index) in client.addresses" :key="address.id">
            <p>
              <strong
                >Adres
                {{ client.addresses.length > 1 ? index + 1 : "" }}:</strong
              >
              {{ address.address1 }}, {{ address.city }}, {{ address.country }}
            </p>
            <p><strong>Telefoonnummer:</strong> {{ address.phone }}</p>
          </div>
        </div>
      </li>
    </ul>
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

        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <button class="page-link" @click="goToPage(currentPage + 1)">
            Volgende
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "ClientOverview",
  props: {
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
      searchQuery: "",
      expandedClient: null,
      clients: [],
      currentPage: 1,
      clientsPerPage: 5, // Display 5 clients per page
      totalClients: 0, // To track the total number of clients
    };
  },
  computed: {
    filteredClients() {
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        return this.clients.filter((client) => {
          return (
            client.id.toString().includes(query) ||
            client.firstname.toLowerCase().includes(query) ||
            client.lastname.toLowerCase().includes(query) ||
            client.email.toLowerCase().includes(query) ||
            client.addresses.some(
              (address) =>
                address.address1.toLowerCase().includes(query) ||
                address.city.toLowerCase().includes(query) ||
                address.country.toLowerCase().includes(query) ||
                address.phone.toLowerCase().includes(query)
            )
          );
        });
      }
      return this.clients;
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.totalClients / this.clientsPerPage));
    },
    pageRange() {
      if (this.totalPages === 0) return [1]; // Ensure at least one page is shown
      let range = [];
      const maxPagesToShow = 10;
      const startPage = Math.max(1, this.currentPage - 4);
      const endPage = Math.min(this.totalPages, startPage + maxPagesToShow - 1);

      for (let page = startPage; page <= endPage; page++) {
        range.push(page);
      }

      return range;
    },
    paginatedClients() {
      return this.filteredClients; // Directly return filtered clients
    },
  },
  methods: {
    async fetchClients() {
      try {
        const response = await axios.get(`${this.apiBaseUrl}/clients`, {
          params: {
            searchterm: this.searchQuery,
            page: this.currentPage, // Use the correct page
            per_page: this.clientsPerPage, // Use the per_page value dynamically
          },
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        });

        if (response.data.status === "success") {
          this.clients = response.data.data.list;
          this.totalClients = response.data.data.pagination.total_items;
        }
      } catch (error) {
        console.error("Error fetching clients:", error);
      }
    },
    async toggleDetails(client, clientId) {
      const newClientId = this.expandedClient === clientId ? null : clientId;

      try {
        const response = await axios.post(
          `${this.apiBaseUrl}/orders/assign-client`,
          { client_id: newClientId },
          {
            headers: { Authorization: `Bearer ${this.token}` },
          }
        );

        if (response.data.status === "success") {
          this.$emit("client-assigned", newClientId);
        }
      } catch (error) {
        console.error("Error assigning client:", error);
      }

      this.expandedClient = newClientId;
      this.$emit("client-selected", newClientId ? client : null);
    },
    onSearchInput() {
      // Reset current page to 1 when search is updated
      this.currentPage = 1;
      this.fetchClients(); // Fetch clients again on search change
    },
    goToPage(page) {
      if (page > 0 && page <= this.totalPages) {
        this.currentPage = page;
        this.fetchClients(); // Fetch clients again when page is changed
      }
    },
  },
  mounted() {
    this.fetchClients();
  },
};
</script>
