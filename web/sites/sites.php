<?php

// phpcs:ignoreFile

/**
 * @file
 * Configuration file for multi-site support and directory aliasing feature.
 *
 * This file is required for multi-site support and also allows you to define a
 * set of aliases that map host names, ports, and path names to configuration
 * directories in the sites directory. These aliases are loaded prior to
 * scanning for directories, and they are exempt from the normal discovery
 * rules. See default.settings.php to view how Drupal discovers the
 * configuration directory when no alias is found.
 *
 * Aliases are useful on development servers, where the domain name may not be
 * the same as the domain of the live server. Since Drupal stores file paths in
 * the database (files, system table, etc.) this will ensure the paths are
 * correct when the site is deployed to a live server.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/sites.php'.
 *
 * Aliases are defined in an associative array named $sites. The array is
 * written in the format: '<port>.<domain>.<path>' => 'directory'. As an
 * example, to map https://www.drupal.org:8080/my-site/test to the configuration
 * directory sites/example.com, the array should be defined as:
 * @code
 * $sites = [
 *   '8080.www.drupal.org.my-site.test' => 'example.com',
 * ];
 * @endcode
 * The URL, https://www.drupal.org:8080/my-site/test/, could be a symbolic link
 * or an Apache Alias directive that points to the Drupal root containing
 * index.php. An alias could also be created for a subdomain. See the
 * @link https://www.drupal.org/documentation/install online Drupal installation guide @endlink
 * for more information on setting up domains, subdomains, and subdirectories.
 *
 * The following examples look for a site configuration in sites/example.com:
 * @code
 * URL: http://dev.drupal.org
 * $sites['dev.drupal.org'] = 'example.com';
 *
 * URL: http://localhost/example
 * $sites['localhost.example'] = 'example.com';
 *
 * URL: http://localhost:8080/example
 * $sites['8080.localhost.example'] = 'example.com';
 *
 * URL: https://www.drupal.org:8080/my-site/test/
 * $sites['8080.www.drupal.org.my-site.test'] = 'example.com';
 * @endcode
 *
 * @see default.settings.php
 * @see \Drupal\Core\DrupalKernel::getSitePath()
 * @see https://www.drupal.org/docs/getting-started/multisite-drupal
 */

// Multisite de demonstracao: departamentos extras rodando no mesmo codebase
// e container, cada um na sua porta/banco de dados proprio (ver README).
$sites['8299.srv1654694.hstgr.cloud'] = 'dfis';
$sites['8399.srv1654694.hstgr.cloud'] = 'demat';
$sites['8499.srv1654694.hstgr.cloud'] = 'demed';
$sites['8599.srv1654694.hstgr.cloud'] = 'defil';
$sites['8699.srv1654694.hstgr.cloud'] = 'delet';
$sites['8799.srv1654694.hstgr.cloud'] = 'depro';
// Aliases extras para testar localmente via DDEV com --uri.
$sites['8299.localhost'] = 'dfis';
$sites['8399.localhost'] = 'demat';
$sites['8499.localhost'] = 'demed';
$sites['8599.localhost'] = 'defil';
$sites['8699.localhost'] = 'delet';
$sites['8799.localhost'] = 'depro';
$sites['8899.srv1654694.hstgr.cloud'] = 'demet';
$sites['8899.localhost'] = 'demet';
$sites['8999.srv1654694.hstgr.cloud'] = 'desoc';
$sites['8999.localhost'] = 'desoc';
$sites['9099.srv1654694.hstgr.cloud'] = 'dequi';
$sites['9099.localhost'] = 'dequi';
$sites['9199.srv1654694.hstgr.cloud'] = 'decom';
$sites['9199.localhost'] = 'decom';
$sites['9299.srv1654694.hstgr.cloud'] = 'decivil';
$sites['9299.localhost'] = 'decivil';
$sites['9399.srv1654694.hstgr.cloud'] = 'deelet';
$sites['9399.localhost'] = 'deelet';
