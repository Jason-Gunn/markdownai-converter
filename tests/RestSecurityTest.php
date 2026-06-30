<?php

use MDAI\Rest;
use PHPUnit\Framework\TestCase;

final class RestSecurityTest extends TestCase
{
    public function testPasswordProtectedPostIsDetected(): void
    {
        $post = new WP_Post();
        $post->post_status = 'publish';
        $post->post_type = 'post';
        $post->post_password = 'secret';

        $method = new ReflectionMethod(Rest::class, 'is_password_protected_post');
        $method->setAccessible(true);

        $result = $method->invoke(null, $post);

        $this->assertTrue($result);
    }

    public function testUnprotectedPostIsNotDetectedAsProtected(): void
    {
        $post = new WP_Post();
        $post->post_status = 'publish';
        $post->post_type = 'post';
        $post->post_password = '';

        $method = new ReflectionMethod(Rest::class, 'is_password_protected_post');
        $method->setAccessible(true);

        $result = $method->invoke(null, $post);

        $this->assertFalse($result);
    }
}