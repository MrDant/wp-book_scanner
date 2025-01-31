<template>
  <div class="cat-container relative">
    <div class="flex gap-1 items-center flex-wrap">
      <div class="badge" v-for="e in model" @click="removeCategory(e.id)">
        {{ e.name }} x
      </div>
      <input
        v-model="q"
        class="border-none shadow-none flex-1 focus:outline-0"
        placeholder="Rechercher une catégorie"
        @focusin="focus = true"
      />
    </div>
    <template v-if="focus">
      <div
        class="fixed top-0 right-0 left-0 bottom-0"
        @click="focus = false"
      ></div>
      <div class="options">
        <div
          v-for="e in data"
          @click="
            model.push(e);
            focus = false;
          "
          class="hover:bg-sky-300 cursor-pointer"
        >
          {{ e.name }}
        </div>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref } from "vue";
import { computedAsync } from "@vueuse/core";
import { search } from "../categories";

const focus = ref(false);
const model = defineModel();
const q = ref("");
const data = computedAsync(async () => {
  return await search(q.value);
}, []);

function removeCategory(id) {
  model.value = model.value.filter((e) => e.id !== id);
}
</script>
<style scoped>
.options {
  @apply absolute left-0 right-0 top-full min-h-11 overflow-hidden p-2 rounded shadow bg-white;
}

.badge {
  @apply rounded-full bg-slate-300 text-xs leading-none px-2 py-1 h-fit cursor-pointer;
}
</style>
