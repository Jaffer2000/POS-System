import { createApp } from "vue";
import router from "./router";
import App from "./vue/App.vue";

window.startPOS = (element, config) => {
  const app = createApp(App, config);
  app.use(router);
  app.mount(element);
};
