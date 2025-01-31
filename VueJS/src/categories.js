import { api } from "./axios";
let categories = null;

async function getCategoryName(id) {
  const cat = await getCategoryById(id);
  if (cat.parent == 0) {
    return cat.name;
  }
  return (await getCategoryName(cat.parent)) + " / " + cat.name;
}
export async function search(q = "") {
  const data = await getCategories();
  return await Promise.all(
    data
      .filter((e) => new RegExp(`.*${q}.*`, "i").test(e.name))
      .map(async (e) => {
        return { id: e.id, name: await getCategoryName(e.id) };
      })
  );
}
export async function getCategoryById(id) {
  return (await getCategories()).find((e) => e.id == id);
}
export async function getCategories() {
  if (categories == null) {
    categories = (await api("/wp-json/wc/v3/products/categories?per_page=100"))
      .data;
  }
  return categories;
}
