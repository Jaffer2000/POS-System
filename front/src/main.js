import { createApp } from "vue";
import router from "./router";
import App from "./App.vue";

window.startPOS = (element, config) => {
  const app = createApp(App, config);
  app.use(router);
  app.mount(element);
};
