<script setup>
import { onMounted, ref, useTemplateRef } from "vue";
import Modal from "./components/modal.vue";
import { wp_data, api, axios } from "./axios";
import { useNotification } from "@kyvg/vue3-notification";
import { getCategoryById } from "./categories";

const { notify } = useNotification();
const isbn = ref("");
const tmpIsbn = ref("");
const modal = useTemplateRef("modal");
const scanTimeout = ref(null);
const loading = ref(false);
const data = ref({
  images: [],
  description: "",
  id: "",
  name: "",
  regular_price: "",
  author: "",
  stock_quantity: null,
  isbn: "",
});
async function search() {
  loading.value = true;

  let product = null;
  try {
    product = (await api.get("/wp-json/wc/v3/products/isbn/" + isbn.value))
      .data;
  } catch {}
  if (product) {
    data.value = {};
    Object.assign(data.value, product);
    const author = product?.attributes.find((e) => e.name == "Autheur");
    if (author) {
      data.value.author = author.terms.map((e) => e.name);
    }
    if (data.value.category_ids) {
      data.value.categories = await Promise.all(
        data.value.category_ids.map(getCategoryById)
      );
    }
  }
  // Si pas d'info livre trouvé
  else {
    const books = (
      await Promise.all([
        await getBookByGoogle(),
        await getBookByOpenLibrary(),
        await getBookByGoodReads(),
      ])
    ).filter((e) => e);
    if (books.length == 0) {
      notify("Aucun livre trouvé");
      loading.value = false;
      return;
    }
    data.value = books[0];
    data.value.images = books
      .reduce((a, b) => {
        a.push(b.images);
        return a;
      }, [])
      .flat();

    ["description", "author", "name"].forEach((key) => {
      if (!data.value[key]) {
        data.value[key] = books.map((e) => e[key]).filter((e) => !!e)[0];
      }
    });
  }
  isbn.value = "";
  modal.value.open = true;
  loading.value = false;
}

async function getBookByGoogle() {
  let book;
  try {
    book =
      (
        await api.get(
          `https://www.googleapis.com/books/v1/volumes?q=isbn:${isbn.value}`
        )
      ).data.items[0].volumeInfo ?? null;
  } catch {
    return null;
  }
  if (!book) return null;
  return {
    images: book.imageLinks ? [book.imageLinks.thumbnail] : [],
    description: book.description,
    name: book.title,
    author: book.authors,
    stock_quantity: 1,
    isbn: isbn.value,
  };
}

async function getBookByOpenLibrary() {
  const book = (
    await axios.get(
      `https://openlibrary.org/api/books?bibkeys=ISBN:${isbn.value}&format=json&jscmd=data`
    )
  ).data["ISBN:" + isbn.value];
  if (!book) return null;
  return {
    images: book.cover ? [book.cover.large] : [],
    description: book.description,
    name: book.title,
    author: book.authors ? book.authors.map((e) => e.name) : [],
    stock_quantity: 1,
    isbn: isbn.value,
  };
}

async function getBookByGoodReads() {
  let form_data = new FormData();
  form_data.append("action", "scan_book");
  form_data.append("isbn", isbn.value);
  try {
    const book = (await axios.post(wp_data.ajax_url, form_data)).data.data;
    return { ...book, stock_quantity: 1, isbn: isbn.value };
  } catch {
    return {};
  }
}

onMounted(() => {
  // Détection du scanner
  document.addEventListener("keypress", (e) => {
    if (scanTimeout.value) {
      clearTimeout(scanTimeout.value); // Réinitialise le timeout
    }
    if (RegExp(/^[A-z0-9]$/gm).test(e.key)) {
      tmpIsbn.value += e.key;
    } else {
      tmpIsbn.value = "";
    }

    scanTimeout.value = setTimeout(() => {
      if (tmpIsbn.value.length >= 12) {
        isbn.value = tmpIsbn.value;
      }
      tmpIsbn.value = "";
    }, 100); // Délai pour considérer le scan terminé
  });
});
</script>
<style>
*:disabled {
  @apply cursor-not-allowed;
}
h1 {
  @apply text-lg font-bold;
}
button {
  @apply bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded;
}

button.danger {
  @apply bg-red-600 hover:bg-red-500 text-white;
}
input,
textarea {
  @apply rounded shadow px-2 py-1;
}
</style>
<template>
  <div class="px-4">
    <h1 class="my-2">Scanner un livre</h1>
    <div class="flex gap-2 items-center">
      <input v-model="isbn" placeholder="isbn" />
      <button
        @click="search"
        class="flex items-center"
        :disabled="loading || isbn == ''"
      >
        <span v-if="loading">
          <svg
            class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
        </span>
        <span> Rechercher</span>
      </button>
    </div>
    <Modal :data="data" ref="modal" />
    <notifications position="bottom right" />
  </div>
</template>
