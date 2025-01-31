<template>
  <div @click="open = false" v-if="open">
    <div
      class="bg-opacity-50 bg-black fixed top-0 left-0 right-0 bottom-0 flex justify-center items-center cursor-pointer"
    >
      <div class="container" @click.stop="">
        <div class="relative self-center">
          <div class="w-64">
            <Carrousel v-model="form.images" />
          </div>
          <div
            v-if="!data.id"
            class="rounded-full absolute top-4 left-4 p-2 leading-none text-white text-xs bg-purple-500"
          >
            Nouveau
          </div>
        </div>
        <h1 class="font-bold text-sky-500">
          <EditInput v-model="form.name" />
        </h1>
        <i class="text-sm leading-none">
          <ArrayStringEdit v-model="form.author" />
        </i>
        <p class="text-sm underline mt-2">Description :</p>
        <EditInput
          class="text-sm"
          :value="data.description"
          v-model="form.description"
          type="textarea"
        />
        <div class="w-full my-2 border"></div>
        <b>Catégories :</b>
        <Categories v-model="form.categories" />
        <b>Qantité :</b>
        <div class="flex gap-1 justify-center">
          <button
            @click="
              form.stock_quantity--;
              form.stock_quantity < 0 ? (form.stock_quantity = 0) : '';
            "
          >
            -
          </button>
          <input v-model="form.stock_quantity" class="text-center w-1/2" />
          <button @click="form.stock_quantity++">+</button>
        </div>
        <b>Prix :</b>
        <div class="flex justify-center items-center">
          <input v-model="form.regular_price" class="flex-1" /><span
            class="text-center px-2"
            >€</span
          >
        </div>
        <p class="text-center mt-2">
          <button @click="submit">
            {{ data.id ? "Enregistrer" : "Ajouter le livre" }}
          </button>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { axios, wp_data } from "../axios";
import { useNotification } from "@kyvg/vue3-notification";
import Carrousel from "./carrousel-edit.vue";
import Categories from "./categories.vue";
import ArrayStringEdit from "./array-string-edit.vue";
import EditInput from "./edit-input.vue";

const { notify } = useNotification();

const open = ref(false);
const { data } = defineProps(["data"]);
const form = ref({});

watch(
  () => data.images + data.categories,
  () => {
    form.value = Object.assign({}, data);
    if (!form.value.images) {
      form.value.images = [];
    }
    if (!form.value.regular_price) {
      form.value.regular_price = 1;
    }
    if (!form.value.categories) {
      form.value.categories = [];
    }
    if (!form.value.name) {
      form.value.name = "Livre inconnu";
    }
  }
);

defineExpose({ open });

async function submit() {
  const dataForm = new FormData();
  dataForm.append("action", "save_product");
  form.value.images = form.value.images.map((e) => {
    return e.id ?? e.src ?? e;
  });
  Object.keys(form.value).forEach((key) => {
    if (!form.value.id || form.value[key] !== data[key] || key == "id") {
      if (typeof form.value[key] == "object") {
        form.value[key].forEach((v) => {
          dataForm.append(key + "[]", v);
        });
      } else {
        dataForm.append(key, form.value[key]);
      }
    }
  });
  form.value.categories.forEach((e) => dataForm.append("category_ids[]", e.id));

  const res = (await axios.post(wp_data.ajax_url, dataForm)).data;
  if (res.success) {
    open.value = false;
  }
  notify(res.data.message);
}
</script>

<style scoped>
.container {
  @apply rounded bg-white shadow p-2 min-w-64 max-w-sm cursor-auto relative overflow-y-auto;
  @apply flex flex-col  gap-1;
  max-height: 80vh;
}
</style>
