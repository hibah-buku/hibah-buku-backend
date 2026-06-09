<?php

use App\Models\Manuscript;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('publisher can submit checklist and approved decision for a preprint manuscript', function () {
    $publisherRole = Role::firstOrCreate(['name' => 'penerbit']);
    $authorRole = Role::firstOrCreate(['name' => 'penulis']);

    $publisher = User::create([
        'name' => 'Publisher',
        'email' => 'publisher@example.com',
        'password' => bcrypt('password123'),
        'role_id' => $publisherRole->id,
        'status' => 'active',
    ]);

    $author = User::create([
        'name' => 'Author',
        'email' => 'author@example.com',
        'password' => bcrypt('password123'),
        'role_id' => $authorRole->id,
        'status' => 'active',
    ]);

    $manuscript = Manuscript::create([
        'user_id' => $author->id,
        'contract_id' => null,
        'title' => 'Naskah Pra-Cetak',
        'book_type' => 'bukuajar',
        'status' => 'preprint',
    ]);

    $token = JWTAuth::attempt(['email' => 'publisher@example.com', 'password' => 'password123']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson("/api/publisher/manuscripts/{$manuscript->id}/decision", [
            'decision' => 'approved',
            'revision_notes' => null,
            'check_notes' => 'Checklist lengkap.',
            'cover_design_ok' => true,
            'page_count_ok' => true,
            'admin_docs_ok' => true,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('publisher_checks', [
        'manuscript_id' => $manuscript->id,
        'publisher_id' => $publisher->id,
        'cover_design_ok' => true,
        'page_count_ok' => true,
        'admin_docs_ok' => true,
    ]);

    $this->assertDatabaseHas('publisher_decisions', [
        'manuscript_id' => $manuscript->id,
        'publisher_id' => $publisher->id,
        'decision' => 'approved',
    ]);

    $this->assertDatabaseHas('manuscripts', [
        'id' => $manuscript->id,
        'status' => 'to_print',
    ]);
});

test('approved decision rejects incomplete checklist', function () {
    $publisherRole = Role::firstOrCreate(['name' => 'penerbit']);
    $authorRole = Role::firstOrCreate(['name' => 'penulis']);

    $publisher = User::create([
        'name' => 'Publisher',
        'email' => 'publisher2@example.com',
        'password' => bcrypt('password123'),
        'role_id' => $publisherRole->id,
        'status' => 'active',
    ]);

    $author = User::create([
        'name' => 'Author 2',
        'email' => 'author2@example.com',
        'password' => bcrypt('password123'),
        'role_id' => $authorRole->id,
        'status' => 'active',
    ]);

    $manuscript = Manuscript::create([
        'user_id' => $author->id,
        'contract_id' => null,
        'title' => 'Naskah Tidak Lengkap',
        'book_type' => 'bukureferensi',
        'status' => 'preprint',
    ]);

    $token = JWTAuth::attempt(['email' => 'publisher2@example.com', 'password' => 'password123']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson("/api/publisher/manuscripts/{$manuscript->id}/decision", [
            'decision' => 'approved',
            'revision_notes' => null,
            'check_notes' => 'Belum lengkap.',
            'cover_design_ok' => true,
            'page_count_ok' => false,
            'admin_docs_ok' => true,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error');

    $this->assertDatabaseCount('publisher_decisions', 0);
});
