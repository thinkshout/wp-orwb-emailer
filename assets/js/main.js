import Alpine from 'alpinejs';
import mediumEditor from 'medium-editor';

Alpine.data('orwbMailer', () => {
  return {
    subject: '',
    messageEditor: null,
    apiKey: '',
    eligibleUsers: [],
    selectedUsers: [],
    hasAPIKey: false,
    hasEligibleUsers() {
      return this.eligibleUsers.length > 0;
    },
    hasSelectedUsers() {
      return this.selectedUsers.length > 0;
    },
    recipientSelected(user) {
      return this.selectedUsers.includes(user);
    },
    selectAll() {
      if (!this.hasEligibleUsers()) return;
      this.toggleRecipient('all');
    },
    selectNone() {
      if (!this.hasSelectedUsers()) return;
      this.toggleRecipient('none');
    },
    toggleRecipient(user) {
      if ( user === 'all' ) {
        this.selectedUsers = this.eligibleUsers;
        this.eligibleUsers = [];
        return;
      }
      if ( user === 'none' ) {
        this.eligibleUsers = [ ...this.eligibleUsers, ...this.selectedUsers];
        this.selectedUsers = [];
        return;
      }
      if (this.recipientSelected(user)) {
        this.selectedUsers.splice(this.selectedUsers.indexOf(user), 1);
        this.eligibleUsers.push(user);
      } else {
        this.selectedUsers.push(user);
        this.eligibleUsers.splice(this.eligibleUsers.indexOf(user), 1);
      }
    },
    async getEligibleUsers() {
      const { ajaxUrl, ajaxSecurity } = orwb_mailer_ajax;
      const data = new FormData();
      data.append('action', 'orwb_mailer_get_eligible_users');
      data.append('security', ajaxSecurity);
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
        }
      );
      const users = await response.json();
      this.eligibleUsers = Object.values(users);
    },
    async checkAPIKey() {
      const { ajaxUrl, ajaxSecurity } = orwb_mailer_ajax;
      const data = new FormData();
      data.append('action', 'orwb_mailer_check_api_key');
      data.append('security', ajaxSecurity);
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data
      });
      const responseData = await response.json();
      this.hasAPIKey = responseData.success;
    },
    async removeAPIkey() {
      const { ajaxUrl, ajaxSecurity } = orwb_mailer_ajax;
      const data = new FormData();
      data.append('action', 'orwb_mailer_remove_api_key');
      data.append('security', ajaxSecurity);
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data
      });
      const responseData = await response.json();
      if (responseData.success) {
        this.hasAPIKey = false;
      }
    },
    async setAPIKey() {
      const { ajaxUrl, ajaxSecurity } = orwb_mailer_ajax;
      const data = new FormData();
      data.append('action', 'orwb_mailer_set_api_key');
      data.append('security', ajaxSecurity);
      data.append('api_key', this.apiKey);
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
        }
      );
      const responseData = await response.json();
      if (responseData.success) {
        this.hasAPIKey = true;
        this.messageEditor = new mediumEditor(document.getElementById('orwb-mailer-message'));
        await this.getEligibleUsers();
      }
    },
    async sendEmail() {
      if (!this.hasSelectedUsers()) {
        alert('Please select at least one user.');
        return;
      }
      if (!this.subject) {
        alert('Please enter a subject.');
        return;
      }
      if (!this.messageEditor.getContent()) {
        alert('Please enter a message.');
        return;
      }
      const { ajaxUrl, ajaxSecurity } = orwb_mailer_ajax;
      const data = new FormData();
      data.append('action', 'orwb_mailer_send_email');
      data.append('security', ajaxSecurity);
      data.append('subject', this.subject);
      data.append('message', this.messageEditor.getContent());
      data.append('selectedUsers', JSON.stringify(this.selectedUsers));
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
      });
      const responseData = await response.json();
      if (responseData.success) {
        alert('Email sent successfully.');
      } else {
        alert(`There was an error sending the email. Message: ${responseData.data}`);
      }
    },
    async init() {
      await this.checkAPIKey();
      if ( ! this.hasAPIKey ) return;
      this.messageEditor = new mediumEditor(document.getElementById('orwb-mailer-message'));
      await this.getEligibleUsers();
    }
  };
})

Alpine.start();