<template>
  <div class="client-overview">
    <h2>Klanten</h2>
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
    <ul class="client-list">
      <li
        v-for="client in clients"
        :key="client.id"
        :class="{ 'client-item': true, expanded: expandedClient === client.id }"
        @click="toggleDetails(client.id)"
      >
        <p class="client-name">{{ client.name }}</p>
        <p class="client-email">{{ client.email }}</p>
        <div v-if="expandedClient === client.id" class="client-details">
          <p><strong>Adres:</strong> {{ client.address }}</p>
          <p><strong>Land:</strong> {{ client.country }}</p>
          <p><strong>Telefoonnummer(s):</strong> {{ client.phone }}</p>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: "ClientOverview",
  data() {
    return {
      searchQuery: "",
      expandedClient: null, // Tracks which client is expanded
      clients: [
        {
          id: 1,
          name: "Jan Jansen",
          email: "jan.jansen@example.com",
          address: "Stationsstraat 12, 1012 AB Amsterdam",
          country: "Netherlands",
          phone: "+31 6 12345678",
        },
        {
          id: 2,
          name: "Piet Pietersen",
          email: "piet.pietersen@example.com",
          address: "Kerkstraat 45, 1234 CD Utrecht",
          country: "Netherlands",
          phone: "+31 6 87654321",
        },
        {
          id: 3,
          name: "Anna de Vries",
          email: "anna.devries@example.com",
          address: "Herenstraat 8, 2345 EF Rotterdam",
          country: "Netherlands",
          phone: "+31 6 23456789",
        },
        {
          id: 4,
          name: "Leon de Jong",
          email: "leon.dejong@example.com",
          address: "Prinsengracht 56, 3456 GH Haarlem",
          country: "Netherlands",
          phone: "+31 6 34567890",
        },
        {
          id: 7,
          name: "Lars Smit",
          email: "lars.smit@example.com",
          address: "Waterkant 33, 6789 MN Antwerpen",
          country: "Belgium",
          phone: "+31 6 67890123",
        },
      ],
    };
  },
  methods: {
    toggleDetails(clientId) {
      // Toggle the visibility of details
      this.expandedClient = this.expandedClient === clientId ? null : clientId;
    },
  },
};
</script>

<style scoped>
.client-overview {
  padding: 20px;
}

h2 {
  margin-bottom: 20px;
}

.client-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.client-item {
  padding: 10px 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-bottom: 15px;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
  background-color: #fff;
}

.client-item:hover {
  background-color: rgba(0, 138, 0, 0.16);
  cursor: pointer;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.expanded {
  background-color: rgba(0, 138, 0, 0.16);
}

.client-name {
  color: #008a00;
  font-size: 20px;
  font-weight: 500;
  margin: 0 0 10px 0;
}

.client-email {
  margin: 0 0 10px 0;
  font-size: 16px;
  color: #555;
}

.client-details {
  margin-top: 15px;
  font-size: 15px;
  color: #444;
  line-height: 1.6;
}

.client-details p {
  margin: 5px 0;
}
</style>
