const { __ } = wp.i18n;

const i18n = {};

i18n.COMMON = {
  PLUGIN_NAME: __('Database Cleaner', 'database-cleaner'),
  TUTORIAL: __('Tutorial', 'database-cleaner'),
  SETTINGS: __('Settings', 'database-cleaner'),
  DASHBOARD: __('Dashboard', 'database-cleaner'),
  BY_JORDY_MEOW: __('By Jordy Meow', 'media-cleaner'),
  TABLE: __('Table', 'database-cleaner'),
  CLOSE: __('Close', 'database-cleaner'),
  USED_BY: __('Used By', 'database-cleaner'),
  SELECTED_ITEMS: __('selected items', 'database-cleaner'),
  UNKNOWN: __('Unknown', 'database-cleaner'),
  ASSIGN_PLUGIN: __('Assign Plugin', 'database-cleaner'),
  RE_ASSIGN_PLUGIN: __('ReAssign Plugin', 'database-cleaner'),
  SEARCH: __('Search', 'database-cleaner'),
  ITEMS_PER_PAGE: __('items per page', 'database-cleaner'),
  CLEAN: __('Clean', 'database-cleaner'),
  DEV_TOOLS: __( 'Dev Tools', 'ai-engine' ),
};

i18n.HELP = {
  DEV_TOOLS: __( 'Enable a new tab with debugging tools. For developers only.', 'ai-engine' ),
  SWEEPER: __( 'Constantly and discreetly cleans your database in bite-sized tasks, keeping your site\'s speed unaffected. All the items set with "Auto" will be processed.', 'ai-engine' ),
};

i18n.CHART_SIZES = {
  THIS_IS_FAKE_DATA: __('This is fake data! 😎', 'database-cleaner'),
};

i18n.DASHBOARD = {
  WELCOME_MESSAGE: __('Welcome to the Database Cleaner Dashboard.', 'database-cleaner'),
};

i18n.CORE_SECTIONS = {
  POSTS: __('Posts', 'database-cleaner'),
  POSTS_METADATA: __('Posts Metadata', 'database-cleaner'),
  USERS: __('Users', 'database-cleaner'),
  COMMENTS: __('Comments', 'database-cleaner'),
  TRANSIENTS: __('Transients', 'database-cleaner'),
  POST_TYPES: __('Post Types', 'database-cleaner'),
};

i18n.HOOKS = {
  POSTS_METADATA_DUPLICATED_WARNING: __('Based on the nature of meta duplicates, it is better to do it manually', 'database-cleaner'),
};

i18n.TABLE_COLUMN_TITLE = {
  DETAILS: __('Details', 'database-cleaner'),
  COUNT: __('Count', 'database-cleaner'),
  MODE: __('Mode', 'database-cleaner'),
  POST_TYPE: __('Post Type', 'database-cleaner'),
  USED_BY: __('Used By', 'database-cleaner'),
  TABLE: __('Table', 'database-cleaner'),
  SIZE: __('Size', 'database-cleaner'),
  RELATIVE_PERCENTAGE: __('Relative %', 'database-cleaner'),
  AUTO: __('Auto', 'database-cleaner'),
  SCHEDULE: __('Schedule', 'database-cleaner'),
  NEXT_RUN: __('Next Run', 'database-cleaner'),
  NAME: __('Name', 'database-cleaner'),
  CLEAN: __('Clean', 'database-cleaner'),
  ACTION: __('Action', 'database-cleaner'),
  POST_ID: __('Post ID', 'database-cleaner'),
  USER_ID: __('User ID', 'database-cleaner'),
};

i18n.FILTER_TITLE = {
  ALL: __('All', 'database-cleaner'),
  NOT_USED: __('Not Used', 'database-cleaner'),
  USED: __('Used', 'database-cleaner'),
  AUTOLOADED: __('Autoloaded', 'database-cleaner'),
  NOT_AUTOLOADED: __('Not Autoloaded', 'database-cleaner'),
  UNKNOWN: __('Unknown', 'database-cleaner'),
  SIZE: __('Size', 'database-cleaner'),
  RELATIVE_PERCENTAGE: __('Relative %', 'database-cleaner'),
  AUTO: __('Auto', 'database-cleaner'),
  SCHEDULE: __('Schedule', 'database-cleaner'),
  NEXT_RUN: __('Next Run', 'database-cleaner'),
};

i18n.CLEAN_STYLE_LABEL = {
  AUTO: __('Auto', 'database-cleaner'),
  MANUAL: __('Manual', 'database-cleaner'),
  NEVER: __('Never', 'database-cleaner'),
};

i18n.AGE_LABEL = {
  NONE: __('None', 'database-cleaner'),
  DAY: __('day', 'database-cleaner'),
  DAYS: __('days', 'database-cleaner'),
  MONTH: __('month', 'database-cleaner'),
  YEAR: __('year', 'database-cleaner'),
};

i18n.MODE = {
  EXPERT: __('Expert', 'database-cleaner'),
  EASY: __('Easy', 'database-cleaner'),
};

i18n.CONTEXT = {
  HIDE_ITEMS_USED_BY_WORDPRESS: __('Hide items used by WordPress', 'database-cleaner'),
  MODAL_DOES_NOT_EXIST: __("The modal {0} doesn't exist.", 'database-cleaner'),
  HISTORICAL_DB_SIZES: __('Historical DB Sizes', 'database-cleaner'),
}

i18n.MODAL = {
  DELETE_ALL: __('Delete All', 'database-cleaner'),
  DATA: __('Data', 'database-cleaner'),
  USED_BY: __('Used By', 'database-cleaner'),
  OPTION_DATA: __('Option data', 'database-cleaner'),
  METADATA: __('Metadata', 'database-cleaner'),
  TABLE: __('Table', 'database-cleaner'),
  FINISHED: __('Finished', 'database-cleaner'),
  ARE_YOU_SURE: __('Are you sure?', 'database-cleaner'),
  WRITE_TO_BROWSER_CONSOLE: __('Write to Browser Console', 'database-cleaner'),
  NO_DATA: __('No data.', 'database-cleaner'),
  NO_ENTRIES: __('No entries.', 'database-cleaner'),
  UNASSIGN: __('Unassign', 'database-cleaner'),
  ASSIGN: __('Assign', 'database-cleaner'),
  SELECTED_PLUGIN_SLUG: __('Selected Plugin Slug', 'database-cleaner'),
  EVERYTHING_CLEAN: __('Everything is clean already!', 'database-cleaner'),
  EVERYTHING_CLEAN_INFO: __('If you want to clean further, give a try to <a href="https://wordpress.org/plugins/media-cleaner/" target="_blank">Media Cleaner</a>. Otherwise, take some time to relax and enjoy playing with <a href="https://wordpress.org/plugins/ai-engine/" target="_blank">AI Engine</a>, the AI plugin for WordPress!', 'database-cleaner'),
  USED_BY_RECOMMEND_PRO_MESSAGE: __("The <b>Pro Version</b> of Database Cleaner knows much more about how data is used, and can simplify this for you! Please check <a href='https://meowapps.com/products/database-cleaner-pro/' target='_blank'>Database Cleaner Pro</a>. And don't hesitate to <a href='https://meowapps.com/contact' target='_blank'>contact me</a> for a discount! 💕", 'database-cleaner'),
  USED_BY_DESCRIPTION: __("Please visit the <b>Settings</b> tab to access all your <b>Used By</b> data. Don't hesitate to share it <a href='https://meowapps.com/support/' target='_blank'>with me</a> so that I can make this plugin better.", 'database-cleaner'),
  EDIT_CUSTOM_QUERY: __('Edit Custom Query', 'database-cleaner'),
  SAVE: __('Save', 'database-cleaner'),
};

i18n.BULK_TASK = {
  STEP: __('STEP', 'database-cleaner'),
  GETTING_TASKS: __('Getting tasks...', 'database-cleaner'),
  CLEANING_ITEMS: __('Cleaning items...', 'database-cleaner'),
  FINAL_MESSAGE_WITH_ERROR: __('The process has finished, but {0} error(s) occurred.', 'database-cleaner'),
  REFRESH_COUNTS: __('Refresh the counts', 'database-cleaner'),
  DELETE_SELECTED_ITEMS: __('Delete the selected items', 'database-cleaner'),
  DELETE: __('Delete the data older than the date threshold', 'database-cleaner'),
  USED_BY: __('Assign Plugin for Selected Items', 'database-cleaner')
}

i18n.SETTINGS = {
  INTRODUCTION: __("While using Database Cleaner is easy, I strongly recommend you to read the <a href='https://meowapps.com/database-cleaner/tutorial/' target='_blank'>tutorial</a> carefully. This plugin has more capabilities than other plugins of the same kind, so you might find new and interesting ways to handle your cleanups. If any issue, or feature request, please let me know on the <a href='https://wordpress.org/support/plugin/database-cleaner/' target='_blank'>WordPress Forums</a>. <b>Last but not least, backup your DB before doing anything!</b> If you don't know how, give a try to <a href='http://meow.click/blogvault' target='_blank'>BlogVault</a>. ", 'database-cleaner'),
  AUTO_CLEAN: __('Auto Clean', 'database-cleaner'),
  TODAY: __('TODAY', 'database-cleaner'),
  WORDPRESS_CORE: __('WordPress Core', 'database-cleaner'),
  POST_TYPES: __('Post Types', 'database-cleaner'),
  LIST_POST_TYPES: __('List Post Types', 'database-cleaner'),
  TABLES: __('Tables', 'database-cleaner'),
  OPTIONS: __('Options', 'database-cleaner'),
  CRON_JOBS: __('Cron Jobs', 'database-cleaner'),
  CUSTOM_QUERIES: __('Custom Queries', 'database-cleaner'),
  SETTINGS_LOGS: __('Settings & Logs', 'database-cleaner'),
  SETTINGS: __('Settings', 'database-cleaner'),
  LICENSE: __('License', 'database-cleaner'),
  AGE_THRESHOLD: __('Age Threshold', 'database-cleaner'),
  AGE_THRESHOLD_DESCRIPTION: __('Auto Clean will be applied on items which are older than the specified number of days, months or years.', 'database-cleaner'),
  MESSAGE: __('Message', 'database-cleaner'),
  MESSAGE_DESCRIPTION: __('Have you read it twice? If yes, hide it :)', 'database-cleaner'),
  PERFORMANCE: __('Performance', 'database-cleaner'),
  BATCH_SIZE: __('Batch Size', 'database-cleaner'),
  BATCH_SIZE_DESCRIPTION: __('The maximum amount of entries to delete at the same time. That will slow down the process a little but will make it work on huge databases.', 'database-cleaner'),
  DELAY_IN_MS: __('Delay (in ms)', 'database-cleaner'),
  DELAY_IN_MS_DESCRIPTION: __("Time to wait between each request (in milliseconds). The overall process is intensive so this gives the chance to your server to chill out a bit. A very good server doesn't need it, but a slow/shared hosting might even reject requests if they are too fast and frequent. Recommended value is actually 0, 100 for safety, 2000 or 5000 if your hosting is kind of cheap.", 'database-cleaner'),
  DEEP_DELETIONS: __('Deep Deletions', 'database-cleaner'),
  DEEP_DELETIONS_DESCRIPTION: __("Items will be deleted by calling the internal WordPress functions, so it will have the side effect of a <i>natural cleaning</i>, as if you were deleting posts (and other elements) naturally.", 'database-cleaner'),
  CUSTOM_USED_BY_DATA: __('Custom <i>Used By</i> data', 'database-cleaner'),
  RESET_USED_BY_DATA: __('Reset Used By', 'database-cleaner'),
  CUSTOM_USED_BY_DATA_DESCRIPTION: __("Did you categorized everything? Nice! ☺️ Please share this data with me through my <a href='https://meowapps.com/contact' target='_blank'>contact form</a>. Thank you! ", 'database-cleaner'),
  USED_BY_POST_TYPES: __('POST TYPES', 'database-cleaner'),
  USED_BY_OPTIONS: __('OPTIONS', 'database-cleaner'),
  USED_BY_TABLES: __('TABLES', 'database-cleaner'),
  USED_BY_CRON_JOBS: __('CRON JOBS', 'database-cleaner'),
  LOGS: __('Logs', 'database-cleaner'),
  CLEAR_LOGS: __('Clear Logs', 'database-cleaner'),
  REFRESH_LOGS: __('Refresh Logs', 'database-cleaner'),
  FOR_ADVANCED_USERS_DEVELOPERS: __('For Advanced Users & Developers', 'database-cleaner'),
  RESET_OPTIONS: __('Reset Options', 'database-cleaner'),
  GENERATE_FAKE_DATA: __('Generate Fake Data', 'database-cleaner'),
  METADATA: __('Metadata', 'database-cleaner'),
  NYAO_SWEEPER: __('Nyao Sweeper', 'database-cleaner'),
  PERFORM_NEXT_TASK: __('Perform Next Task', 'database-cleaner'),
  RESET_TASKS: __('Reset Tasks', 'database-cleaner'),
  SWEEPER: __('Sweeper', 'database-cleaner'),
  SCHEDULE: __('Schedule', 'database-cleaner'),
  SCHEDULE_5_MINUTES: __('Every 5 Minutes', 'database-cleaner'),
  SCHEDULE_10_MINUTES: __('Every 10 Minutes', 'database-cleaner'),
  SCHEDULE_30_MINUTES: __('Every 30 Minutes', 'database-cleaner'),
  SCHEDULE_HOURLY: __('Hourly', 'database-cleaner'),
  SCHEDULE_TWICE_DAILY: __('Twice Daily', 'database-cleaner'),
  SCHEDULE_DAILY: __('Daily', 'database-cleaner'),
  ENABLE: __('Enable', 'database-cleaner'),
  ENABLED: __('Enabled', 'database-cleaner'),
  MODULES: __('Modules', 'database-cleaner'),
  BUILD_INDEXES: __('Build Indexes', 'database-cleaner'),
  REMOVE_INDEXES: __('Remove Indexes', 'database-cleaner'),
}

i18n.CUSTOM_QUERIES = {
  NAME: __('Name', 'database-cleaner'),
  CLEAN: __('Clean', 'database-cleaner'),
  QUERY_COUNT: __('Query Count', 'database-cleaner'),
  QUERY_DELETE: __('Query Delete', 'database-cleaner'),
  RUN_COUNT: __('Run Count', 'database-cleaner'),
  RUN_DELETE: __('Run Delete', 'database-cleaner'),
  BACK: __('Back', 'database-cleaner'),
  ADD_CUSTOM: __('Add Custom', 'database-cleaner'),
  SETUP_NOT_COMPLETE: __('The setup is not complete.', 'database-cleaner'),
}

i18n.TABLES = {
  OPTIMIZE: __('Optimize', 'database-cleaner'),
  REPAIR: __('Repair', 'database-cleaner'),
  TABLE: __('Table', 'database-cleaner'),
  INFO_TABLE_DROP: __('Remove this table', 'database-cleaner'),
  INFO_TABLE_OPTIMIZE: __('Optimize this table (check the tutorial to learn more about it)', 'database-cleaner'),
  INFO_TABLE_REPAIR: __('Repair this table', 'database-cleaner'),
  INFO_TABLE_BULK_DROP: __('Remove the selected tables', 'database-cleaner'),
  INFO_TABLE_BULK_OPTIMIZE: __('Optimize the selected tables (check the tutorial to learn more about it)', 'database-cleaner'),
  INFO_TABLE_BULK_REPAIR: __('Repair the selected tables', 'database-cleaner'),
}

export default i18n;