import { createApp } from "vue";
import "./style.css";
import App from "./App.vue";
import Notifications from "@kyvg/vue3-notification";

createApp(App).use(Notifications).mount("#app");
