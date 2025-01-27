<template>
  <div class="login-container">
    <h2 class="login-title">Login</h2>
    <form @submit.prevent="handleLogin" class="login-form">
      <div class="form-group">
        <label for="username" class="form-label">Gebruikersnaam</label>
        <input
          id="username"
          v-model="username"
          type="text"
          placeholder="Gebruikersnaam"
          class="form-input"
          required
        />
      </div>
      <div class="form-group">
        <label for="password" class="form-label">Wachtwoord</label>
        <input
          id="password"
          v-model="password"
          type="password"
          placeholder="Wachtwoord"
          class="form-input"
          required
        />
      </div>
      <div class="form-group">
        <label for="workstation" class="form-label">Kassa</label>
        <select
          v-model="workstationId"
          id="workstation"
          class="form-select"
          required
        >
          <option v-for="ws in workstations" :key="ws.id" :value="ws.id">
            {{ ws.name }}
          </option>
        </select>
      </div>
      <button type="submit" class="login-button">Inloggen</button>
    </form>
    <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      username: "",
      password: "",
      role: "cashier",
      workstationId: null,
      workstations: [],
      errorMessage: "",
    };
  },
  async created() {
    try {
      // Fetch workstations from the API
      const response = await axios.get(
        `${this.$props.apiBaseUrl}/workstations`
      );
      this.workstations = response.data.data;

      // Check localStorage for a saved workstation ID
      const savedWorkstationId = localStorage.getItem("lastWorkstationId");
      if (
        savedWorkstationId &&
        this.workstations.some((ws) => ws.id === savedWorkstationId)
      ) {
        this.workstationId = savedWorkstationId; // Preselect saved workstation
      }
    } catch (error) {
      this.errorMessage = "Failed to load workstations.";
    }
  },
  methods: {
    async handleLogin() {
      try {
        if (!this.workstationId) {
          this.errorMessage = "Please select a workstation.";
          return;
        }

        const response = await axios.post(`${this.$props.apiBaseUrl}/users`, {
          username: this.username,
          password: this.password,
          role: this.role,
          workstationId: this.workstationId,
        });

        const { token, expiresIn } = response.data.data; // Ensure 'expiresIn' is included in the response
        console.log("Token received from login:", token); // Debugging

        // Save the selected workstation in localStorage
        localStorage.setItem("lastWorkstationId", this.workstationId);

        // Calculate and store token expiration time
        const tokenExpirationTime = Date.now() + expiresIn * 1000; // Convert seconds to milliseconds
        localStorage.setItem("tokenExpirationTime", tokenExpirationTime);

        // Emit token to parent component (app.vue)
        this.$emit("login-success", token.value);
      } catch (error) {
        this.errorMessage = this.translations.loginFailed;
        console.error("Login error:", error);
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
};
</script>
