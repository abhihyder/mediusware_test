<template>
  <section>
    <div class="row">
      <div class="col-md-6">
        <div class="card shadow mb-4">
          <div class="card-body">
            <div class="form-group">
              <label for="">Product Name</label>
              <input
                type="text"
                v-model="product_name"
                placeholder="Product Name"
                class="form-control"
              />
              <small v-if="errors.title" class="text-danger">{{
                errors.title[0]
              }}</small>
            </div>
            <div class="form-group">
              <label for="">Product SKU</label>
              <input
                type="text"
                v-model="product_sku"
                placeholder="Product Name"
                class="form-control"
              />
              <small v-if="errors.sku" class="text-danger">{{
                errors.sku[0]
              }}</small>
            </div>
            <div class="form-group">
              <label for="">Description</label>
              <textarea
                v-model="description"
                id=""
                cols="30"
                rows="4"
                class="form-control"
              ></textarea>
              <small v-if="errors.description" class="text-danger">{{
                errors.description[0]
              }}</small>
            </div>
          </div>
        </div>

        <div class="card-deck mb-4">
          <div
            class="card"
            v-for="(product_image, index) in old_product_images"
          >
            <img
              class="img-thumbnail"
              :src="'/' + product_image.file_path"
              alt="Card image cap"
            />

            <div class="card-footer">
              <button
                class="btn btn-primary btn-sm btn-block"
                @click="removeImage(product_image.id, index)"
              >
                Remove
              </button>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <div
            class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
          >
            <h6 class="m-0 font-weight-bold text-primary">Media</h6>
          </div>
          <div class="card-body border">
            <vue-dropzone
              ref="myVueDropzone"
              id="dropzone"
              :options="dropzoneOptions"
            ></vue-dropzone>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow mb-4">
          <div
            class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
          >
            <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
          </div>
          <div class="card-body">
            <div class="row" v-for="(item, index) in product_variant">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="">Option</label>
                  <select v-model="item.option" class="form-control">
                    <option v-for="variant in variants" :value="variant.id">
                      {{ variant.title }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label
                    v-if="product_variant.length != 1"
                    @click="
                      product_variant.splice(index, 1);
                      checkVariant;
                    "
                    class="float-right text-primary"
                    style="cursor: pointer"
                    >Remove</label
                  >
                  <label v-else>.</label>
                  <input-tag
                    v-model="item.tags"
                    @input="checkVariant"
                    class="form-control"
                  ></input-tag>
                </div>
              </div>
            </div>
          </div>
          <div
            class="card-footer"
            v-if="
              product_variant.length < variants.length &&
              product_variant.length < 3
            "
          >
            <button @click="newVariant" class="btn btn-primary">
              Add another option
            </button>
          </div>

          <div class="card-header text-uppercase">Preview</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <td>Variant</td>
                    <td>Price</td>
                    <td>Stock</td>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="variant_price in product_variant_prices">
                    <td>{{ variant_price.title }}</td>
                    <td>
                      <input
                        type="text"
                        class="form-control"
                        v-model="variant_price.price"
                      />
                    </td>
                    <td>
                      <input
                        type="text"
                        class="form-control"
                        v-model="variant_price.stock"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <button @click="saveProduct" type="submit" class="btn btn-lg btn-primary">
      Save
    </button>
    <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
  </section>
</template>

<script>
import vue2Dropzone from "vue2-dropzone";
import "vue2-dropzone/dist/vue2Dropzone.min.css";
import InputTag from "vue-input-tag";

export default {
  components: {
    vueDropzone: vue2Dropzone,
    InputTag,
  },
  props: {
    product: {
      type: Object,
      required: true,
    },
    product_variants: {
      type: Array,
      required: true,
    },
    variant_prices: {
      type: Array,
      required: true,
    },
    product_images: {
      type: Array,
      required: true,
    },
    variants: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      product_name: "",
      product_sku: "",
      description: "",
      images: [],
      old_product_images: [],
      removed_product_images: [],
      product_variant: [],
      old_variant_prices: [],
      product_variant_prices: [],
      dropzoneOptions: {
        url: "/product-image",
        thumbnailWidth: 150,
        maxFilesize: 0.5,
        addRemoveLinks: true,
        uploadMultiple: true,
        withCredentials: true,
        autoProcessQueue: false,
        params: {
          product_id: "",
        },
        headers: {
          "X-CSRF-TOKEN": document.head.querySelector("[name=csrf-token]")
            .content,
        },
      },
      errors: {},
    };
  },
  methods: {
    // it will push a new object into product variant
    initData() {
      this.product_name = this.product.title;
      this.product_sku = this.product.sku;
      this.description = this.product.description;
      this.old_variant_prices = this.variant_prices;
      this.old_product_images = this.product_images;
    },

    currentVariant() {
      let option = "";
      let tags = [];
      let product_variants = this.product_variants;
      let variants = this.variants;

      for (let i = 0; i < variants.length; i++) {
        for (let x = 0; x < product_variants.length; x++) {
          if (variants[i].id == product_variants[x].variant_id) {
            option = product_variants[x].variant_id;
            tags.push(product_variants[x].variant);
          }
        }

        if (option && tags.length > 0) {
          this.product_variant.push({
            option: option,
            tags: tags,
          });
          option = "";
          tags = [];
        }
      }
      this.setVariantPrice();
    },

    newVariant() {
      let all_variants = this.variants.map((el) => el.id);
      let selected_variants = this.product_variant.map((el) => el.option);
      let available_variants = all_variants.filter(
        (entry1) => !selected_variants.some((entry2) => entry1 == entry2)
      );
      // console.log(available_variants)

      this.product_variant.push({
        option: available_variants[0],
        tags: [],
      });
    },

    // check the variant and render all the combination
    checkVariant() {
      let tags = [];
      this.product_variant_prices = [];
      this.product_variant.filter((item) => {
        tags.push(item.tags);
      });

      this.getCombn(tags).forEach((item) => {
        this.product_variant_prices.push({
          title: item,
          price: 0,
          stock: 0,
        });
      });
    },

    setVariantPrice() {
      let i = 0;
      let tags = [];
      this.product_variant.filter((item) => {
        tags.push(item.tags);
      });

      this.getCombn(tags).forEach((item) => {
        this.product_variant_prices.push({
          title: item,
          price: this.old_variant_prices[i].price,
          stock: this.old_variant_prices[i].stock,
        });
        i++;
      });
    },

    removeImage(product_id, index) {
      this.removed_product_images.push(product_id);
      this.old_product_images.splice(index, 1);
    },
    // combination algorithm
    getCombn(arr, pre) {
      pre = pre || "";
      if (!arr.length) {
        return pre;
      }
      let self = this;
      let ans = arr[0].reduce(function (ans, value) {
        return ans.concat(self.getCombn(arr.slice(1), pre + value + "/"));
      }, []);
      return ans;
    },

    // store updated product into database
    saveProduct() {
      let update_product = {
        title: this.product_name,
        sku: this.product_sku,
        description: this.description,
        product_image: this.images,
        product_variant: this.product_variant,
        product_variant_prices: this.product_variant_prices,
        removed_images: this.removed_product_images,
      };

      axios
        .put("/product/" + this.product.id, update_product)
        .then((response) => {
          if (response.data.success) {
            console.log(response.data.success.data);
            this.dropzoneOptions.params.product_id =
              response.data.success.data.id;
            this.$refs.myVueDropzone.processQueue();
            window.location.href = "/product";
          } else {
            this.errors = response.data.error.errors;
          }
        })
        .catch((error) => {
          this.errors = error;
        });
    },
  },
  mounted() {
    this.initData();
    this.currentVariant();
  },
};
</script>
