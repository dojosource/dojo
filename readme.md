# Dojo (beta)

Manage and grow your Martial Arts school with easy to use tools for your students, teachers and you!

We are currently in Beta and excited to see this plugin begin to make a difference in the Martial Arts community.
As we work out rough edges and push toward an official v1.0 we highly value your feedback!

The Dojo plugin is built primarily for managing a Martial Arts school with a member interface on your web site.
Dojo aims to simplify contract management and give members self-serve options so you can focus on running a great program!

Here's what you can expect in this plugin:

* Fully a WordPress solution. No iframes or linking members off to other sites, this is running on *your* site.
* Add all the programs you offer with optional age ranges.
* Set up membership contracts with configuration details like family pricing, registration fees, cancellation policies, terms links, and attached forms for download.
* Configure your own ranking system and have any number of rank types, like belt ranks and collar ranks.
* Member workflow ready to go that takes users through sign up, adding family members, selecting a membership and submitting a membership application with all the options you configured.
* Administrator dashboard where you can manage students and accept new applications.
* Member dashboard where members can see the status of their membership, manage their monthly billing day, and add new students.
* Developer hooks for extending and customizing.
* And of course, mobile friendly. Everything is designed to be responsive.

For now, of course, you can also expect a beta version experience:

* No multisite support yet
* Not localization friendly yet
* There are going to be some rough edges. If you find some, please let us know!

Pro Add-Ons (not included in this plugin):

There are multiple add-ons available from Dojo Source and more to come. The Invoices add-on we intend to keep free and is currently
available to everyone. The other add-ons are currently being made available to a limited number of beta testers.

* **Invoices** adds invoices to every transaction so members can see their payment history and details.
* **Payments** adds online payment options to invoices and handles automatic recurring payments. Members can manage their payment methods online.
* **Events** adds a custom post type for events and integrates with family pricing, invoices and payments for online registration.

## Installation

### Load and activate the plugin
1. Upload the plugin files to the `/wp-content/plugins/dojo` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

### Check your permalinks
* Go to Settings -> Permalinks and verify you do **not** have it set to, "Plain". The member pages will not work otherwise.

### Set up your Dojo!
* Find My Dojo in the main admin menu.
* Select **My Dojo -> Programs** and add all the programs you offer. (or just a couple to get started)
* Select **My Dojo -> Contracts** and set up contracts that will be your membership options. The last option on the contract is to select which programs that contract has access to.
* Select **My Dojo -> Documents** and upload any waivers or other such documents you want to attach to contracts. You can go back into the contracts and select them.
* Select **My Dojo -> Ranks** and add at least one rank type. I would start with rank type, "Belt" then add all the ranks under that type.
* Select **My Dojo -> Settings** to view your settings options. The default url to the member pages is /members, you can change that here if you like. Just put in the name you want without any slashes.

You are ready to go! Just navigate to your members page (yoursite.com/members if you didn't change it) and try it out!

To view notifications and respond to membership applications go to My Dojo -> Dashboard or just click on My Dojo.


## Frequently Asked Questions

### Why am I not seeing the member page at /members?

Make sure your permalinks settings at Settings -> Permalinks are **NOT** set to, "Plain".

### How do I get the free Invoices add-on from Dojo Source?

1. Create a login at Dojo Source and add your domain name.
2. Copy the key for your domain to **My Dojo -> Settings -> Site Key**
3. An option will come up to download the Invoices add-on

