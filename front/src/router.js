import { createRouter, createWebHistory } from "vue-router";
import OrderOverview from "./components/OrderOverview.vue";
import OrderDetails from "./components/OrderDetails.vue";

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
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
