import axios from "axios";

const wp_data =
  window.wp_data ??
  Object.fromEntries(
    Object.entries(import.meta.env).map(([key, value]) => {
      return [key.replace("VITE_", ""), value];
    })
  );

const auth = `Basic ${btoa(
  `${wp_data.consumer_key}:${wp_data.consumer_secret}`
)}`;

const api = axios.create({
  baseURL: wp_data.BASE_URL ?? "/",
  headers: {
    Authorization: auth,
  },
});

export { wp_data, api, axios };
