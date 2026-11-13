<?php
/**
 * Keluar dari panel admin. Hanya menerima POST + token CSRF
 * (supaya website lain tidak bisa "memaksa" admin keluar lewat tautan).
 */

require dirname(__DIR__) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';

start_session();
admin_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/');
}

csrf_verify();
destroy_session(true);
flash('success', 'Kamu sudah keluar dari panel admin.');
redirect('/admin/login.php');
