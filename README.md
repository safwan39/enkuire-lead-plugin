# Enkuire Lead - Contact Form 7 Integration

## Overview
Enkuire Lead is a WordPress plugin that captures leads from Contact Form 7 submissions and transfers them to the Enkuire App. This plugin serves as a bridge between your WordPress website's contact forms and your lead management system in Enkuire.

## Features
- Automatically captures form submissions from Contact Form 7
- Maps common form fields to Enkuire lead fields
- Tracks user information including device, IP address, and campaign source
- Supports multiple Enkuire endpoints
- Simple configuration through WordPress admin interface

## Field Mapping
The plugin automatically maps common form fields to Enkuire lead fields:
- Name fields (any field ending with "name") → lead_name
- Email fields → lead_email
- Phone fields (contact, mobile, phone) → lead_phone
- UTM fields → lead_utm

## Additional Data Captured
- Campaign name (from form title)
- User device information
- Website address
- User IP address

## Installation
1. Upload the plugin files to the `/wp-content/plugins/cf7-enkuire` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the plugin settings under 'Settings > Enkuire'

## Configuration
In the WordPress admin panel:
1. Go to Settings > Enkuire
2. Enter your Enkuire endpoint URL(s)
3. Set a default group ID
4. Save your settings

## Requirements
- WordPress
- Contact Form 7 plugin

## Developer
Developed by [Caspian Digital Solution](https://caspiands.com/)

## Version
1.1.0