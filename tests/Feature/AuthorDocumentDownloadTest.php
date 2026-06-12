<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Author;
use App\Models\Contract;
use App\Models\Manuscript;
use App\Models\AuthorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup roles
    $this->authorRole = Role::create(['name' => 'penulis']);
    $this->otherRole = Role::create(['name' => 'admin']);

    // Setup writer user
    $this->user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password123'),
        'role_id' => $this->authorRole->id,
        'status' => 'active'
    ]);

    // Setup author record
    $this->author = Author::create([
        'user_id' => $this->user->id,
        'institution' => 'Universitas Indonesia',
        'field_of_study' => 'Teknik Informatika'
    ]);

    // Setup dummy contract
    $this->contract = Contract::create([
        'author_id' => $this->author->id,
        'file_path' => 'contracts/contract.pdf',
        'original_name' => 'contract.pdf',
        'status' => 'contract_validated',
        'validated_at' => now()
    ]);

    // Setup manuscript
    $this->manuscript = Manuscript::create([
        'user_id' => $this->user->id,
        'contract_id' => $this->contract->id,
        'title' => 'Test Manuscript Title',
        'book_type' => 'bukuajar',
        'status' => 'draft_uploaded'
    ]);

    // Fake public disk
    Storage::fake('public');

    // Create a dummy file in public storage
    $this->filePath = 'author_documents/test_statement.pdf';
    Storage::disk('public')->put($this->filePath, 'Dummy PDF content');

    // Create author document
    $this->document = AuthorDocument::create([
        'manuscript_id' => $this->manuscript->id,
        'document_type' => 'surat_pernyataan',
        'file_path' => $this->filePath,
        'file_size_kb' => 10,
        'is_verified' => false,
        'uploaded_at' => now()
    ]);

    // Generate JWT token
    $this->token = JWTAuth::fromUser($this->user);
});

test('authenticated author can download their own document', function () {
    $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
        ->get("/api/manuscripts/me/documents/surat_pernyataan/download");

    $response->assertStatus(200);
    $response->assertHeader('content-disposition', 'attachment; filename=SURAT_PERNYATAAN.pdf');
});

test('unauthenticated user cannot download the document', function () {
    $response = $this->getJson("/api/manuscripts/me/documents/surat_pernyataan/download");

    $response->assertStatus(401);
});

test('authenticated author receives 404 for non-existent document type', function () {
    $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
        ->getJson("/api/manuscripts/me/documents/scan_bermeterai/download");

    $response->assertStatus(404);
});
