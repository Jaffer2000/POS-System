import { createRouter, createWebHistory } from "vue-router";
import OrderOverview from "./components/OrderOverview.vue";
import OrderDetails from "./components/OrderDetails.vue";
import ClientOverview from "./components/ClientOverview.vue";

const routes = [
  {
    path: "/index.html",
    name: "Home",
    props: true,
  },
  {
    path: "/bestellingen",
    name: "Bestellingen",
    component: OrderOverview,
  },
  {
    path: "/bestellingen/:id",
    name: "OrderDetails",
    component: OrderDetails,
    props: true,
  },
  {
    path: "/klanten",
    name: "Klanten",
    component: ClientOverview,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
