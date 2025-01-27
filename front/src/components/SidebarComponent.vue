<template>
  <aside class="sidebar">
    <nav>
      <ul class="main-links">
        <li>
          <router-link to="/index.html"
            ><i class="fas fa-home"></i><span>Home</span>
          </router-link>
        </li>
        <li>
          <a href="#"><i class="fas fa-users"></i><span>Klanten</span></a>
        </li>
        <li>
          <router-link to="/bestellingen">
            <i class="fas fa-shopping-cart"></i><span>Bestellingen</span>
          </router-link>
        </li>
        <li>
          <a href="#"
            ><i class="fas fa-chart-bar"></i><span>Rapportage</span></a
          >
        </li>
      </ul>
      <div class="help-section">
        <ul>
          <li>
            <a href="#"
              ><i class="fas fa-question-circle"></i><span>Help</span></a
            >
          </li>
        </ul>
      </div>
    </nav>
  </aside>
</template>

<script>
export default {
  name: "SidebarComponent",
};
</script>
