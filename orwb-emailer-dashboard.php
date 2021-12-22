<div class="relative mt-5 mr-5" x-data="orwbMailer">
  <div class="bg-gray-200 w-full h-full py-8 px-4 rounded-t">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
      <?php _e( 'Wine Board Bulk Emailer', 'orwb-bulk-emailer' ); ?>
    </h2>
    <p><?php _e( "An emailer that allows Oregon Wine Board admins to send bulk reminder emails to to users with Listing posts that haven't been updated in the last 6 months.", 'orwb-bulk-emailer'); ?></p>
    <template x-if="hasAPIKey">
      <section class="py-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Message</h3>
        <form>
          <label class="text-base text-gray-800 mb-1">Email Subject</label>
          <input type="text" class="w-full p-2 border-2 border-gray-400 mb-2" x-model="subject"/>
          <label class="text-base text-gray-800 mb-1">Email Body</label>
          <textarea id="orwb-mailer-message" class="w-full p-2 border-2 rounded mb-2 border-gray-400 bg-white" placeholder="Raw message preview. Type in the box above to get started." readonly></textarea>
        </form>
      </section>
    </template>
    <section class="py-4">
      <h3 class="text-2xl font-bold text-gray-800 mb-4">Mailgun Api Key</h3>
      <template x-if="!hasAPIKey">
        <div class="flex flex-row flex-wrap">
          <input type="text" class="w-full p-2 border-2 border-gray-400 mb-2" placeholder="API Key" x-model="apiKey"/>
          <button 
            class="p-2 text-white rounded cursor-pointer shadow hover:shadow-lg bg-blue-500 transition-all"
            @click.prevent="setAPIKey"
          >
            Set API Key
          </button>
        </div>
      </template>
      <template x-if="hasAPIKey">
        <div class="flex flex-row flex-wrap">
          <input type="password" class="w-full p-2 border-2 border-gray-400 mb-2 cursor-not-allowed" readonly value="mailgunapikey" />
          <button 
            class="p-2 text-white rounded cursor-pointer shadow hover:shadow-lg bg-red-500 transition-all"
            @click.prevent="removeAPIkey"
          >
            Remove API Key
          </button>
        </div>
      </template>
    </section>
    <template x-if="hasAPIKey">
      <section class="pt-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Recipients</h3>
        <p class="text-gray-600 font-medium mb-2">
          <?php _e( "These are users with Listing Posts that haven't been modified in the last 6 months. Please select which of them should receive an email.", 'orwb-bulk-emailer' ); ?>
        </p>
        <div class="flex flex-row items-stretch justify-between">
          <div class="w-1/2 pr-4">
            <fieldset class="bg-gray-50 pb-4 px-2 rounded h-full">
              <div class="w-full">
                <h3 class="text-xl font-bold my-1 text-gray-800">Eligible Recipients</h3>
                <button
                  class="w-full p-2 mb-4 text-white transition-all rounded"
                  :class="hasEligibleUsers() ? 'bg-blue-500 cursor-pointer shadow hover:shadow-lg' : 'cursor-not-allowed bg-gray-500'"
                  :disabled="!hasEligibleUsers"
                  @click="selectAll"
                >Select All</button>
              </div>
              <div class="w-full max-h-72 overflow-scroll">
                <div>
                  <template x-for="(eligibleUser, index) in eligibleUsers">
                    <label
                      class="grid grid-cols-3 p-1 relative transition-all cursor-pointer hover:bg-gray-300"
                      :class="index % 2 ? 'bg-gray-200' : 'bg-gray-100'"
                    >
                      <input type="checkbox" :checked="recipientSelected(eligibleUser)" class="hidden absolute invisible w-0 h-0" @change="toggleRecipient(eligibleUser)" />
                      <span class="text-gray-600 font-bold col-span-1" x-text="eligibleUser.email"></span>
                      <div class="col-span-2 text-right">
                        <span class="text-gray-600">
                          <span x-text="eligibleUser.posts.length"></span>
                          Posts
                        </span>
                      </div>
                    </label>
                  </template>
                </div>
              </div>
            </fieldset>
          </div>
          <div class="w-1/2 pl-4">
            <fieldset class="bg-gray-50 pb-4 px-2 rounded h-full">
              <div class="w-full">
                <h3 class="text-xl font-bold my-1 text-gray-800">Selected Recipients</h3>
                <button
                  class="w-full p-2 mb-4 text-white transition-all rounded"
                  :class="hasSelectedUsers() ? 'bg-red-500 cursor-pointer shadow hover:shadow-lg' : 'cursor-not-allowed bg-gray-500'"
                  :disabled="!hasSelectedUsers"
                  @click="selectNone"
                >Select None</button>
              </div>
              <div class="w-full max-h-72 overflow-scroll">
                <div>
                  <template x-for="(selectedUser, index) in selectedUsers">
                    <label
                      class="grid grid-cols-3 p-1 relative transition-all cursor-pointer hover:bg-gray-300"
                      :class="index % 2 ? 'bg-gray-200' : 'bg-gray-100'"
                    >
                      <input type="checkbox" :checked="recipientSelected(selectedUser)" class="hidden absolute invisible w-0 h-0" @change="toggleRecipient(selectedUser)" />
                      <span class="text-gray-600 font-bold col-span-1" x-text="selectedUser.email"></span>
                      <div class="col-span-2 text-right">
                        <span class="text-gray-600">
                          <span x-text="selectedUser.posts.length"></span>
                          Posts
                        </span>
                      </div>
                    </label>
                  </template>
                </div>
              </div>
            </fieldset>
          </div>
        </div>
      </section>
    </template>
  </div>
  <footer>
    <template x-if="hasAPIKey">
      <button
        class="w-full p-2 text-white rounded-b transition-all"
        :class="hasSelectedUsers() ? 'bg-blue-500 cursor-pointer shadow hover:shadow-lg' : 'cursor-not-allowed bg-gray-500'"
        :disabled="!hasSelectedUsers"
        @click.prevent="sendEmail"
      >
        Send Email
      </button>
    </template>
  </footer>
</div>