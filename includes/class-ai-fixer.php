<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_AI_Fixer {

	/* ── Generate suggestions for a single post ──────────────────────── */
	public static function suggest( int $post_id, string $field ): array {
		$post = get_post( $post_id );
		if ( ! $post ) return [ 'error' => 'Post bulunamadı.' ];

		$api_key  = get_option( 'merdusseo_ai_api_key', '' );
		$provider = get_option( 'merdusseo_ai_provider', 'openai' );
		$model    = get_option( 'merdusseo_ai_model', 'gpt-4o-mini' );

		if ( empty( $api_key ) ) {
			return [ 'error' => 'AI API anahtarı ayarlanmamış. Lütfen Ayarlar sayfasından API anahtarınızı girin.' ];
		}

		$content_excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 200 );
		$current_title   = MerdusSEO_Meta_Analyzer::get_meta_title( $post );
		$current_desc    = MerdusSEO_Meta_Analyzer::get_meta_desc( $post );

		$prompt = self::build_prompt( $field, $post->post_title, $content_excerpt, $current_title, $current_desc );

		if ( $provider === 'anthropic' ) {
			return self::call_anthropic( $api_key, $model, $prompt );
		}

		return self::call_openai( $api_key, $model, $prompt );
	}

	/* ── Prompt builder ──────────────────────────────────────────────── */
	private static function build_prompt( string $field, string $post_title, string $excerpt, string $current_title, string $current_desc ): string {
		$base = "You are an expert SEO copywriter. Based on the following blog post information, generate SEO-optimized content in the SAME LANGUAGE as the post title and content.\n\n";
		$base .= "Post Title: {$post_title}\n";
		$base .= "Content Excerpt: {$excerpt}\n";
		$base .= "Current Meta Title: {$current_title}\n";
		$base .= "Current Meta Description: {$current_desc}\n\n";

		if ( $field === 'title' ) {
			$base .= "Generate 3 SEO-optimized meta title options. Requirements:\n";
			$base .= "- Each title between 50-60 characters\n";
			$base .= "- Include primary keyword naturally\n";
			$base .= "- Compelling and click-worthy\n";
			$base .= "- Same language as post\n\n";
			$base .= "Return ONLY a JSON array with 3 strings. Example: [\"Title 1\", \"Title 2\", \"Title 3\"]";
		} else {
			$base .= "Generate 3 SEO-optimized meta description options. Requirements:\n";
			$base .= "- Each description between 140-160 characters\n";
			$base .= "- Include primary keyword naturally\n";
			$base .= "- Include a call-to-action\n";
			$base .= "- Same language as post\n\n";
			$base .= "Return ONLY a JSON array with 3 strings. Example: [\"Desc 1\", \"Desc 2\", \"Desc 3\"]";
		}

		return $base;
	}

	/* ── OpenAI API call ─────────────────────────────────────────────── */
	private static function call_openai( string $api_key, string $model, string $prompt ): array {
		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( [
				'model'    => $model,
				'messages' => [
					[ 'role' => 'system', 'content' => 'You are an SEO expert. Always respond with valid JSON only.' ],
					[ 'role' => 'user',   'content' => $prompt ],
				],
				'max_tokens'  => 500,
				'temperature' => 0.7,
			] ),
		] );

		return self::parse_ai_response( $response );
	}

	/* ── Anthropic API call ──────────────────────────────────────────── */
	private static function call_anthropic( string $api_key, string $model, string $prompt ): array {
		$ant_model = $model ?: 'claude-haiku-4-5-20251001';

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
			'timeout' => 30,
			'headers' => [
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type'      => 'application/json',
			],
			'body' => wp_json_encode( [
				'model'      => $ant_model,
				'max_tokens' => 500,
				'messages'   => [
					[ 'role' => 'user', 'content' => $prompt ],
				],
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['content'][0]['text'] ?? '';

		return self::parse_suggestions( $text );
	}

	/* ── Response parser ─────────────────────────────────────────────── */
	private static function parse_ai_response( $response ): array {
		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$msg = $body['error']['message'] ?? 'API hatası: HTTP ' . $code;
			return [ 'error' => $msg ];
		}

		$text = $body['choices'][0]['message']['content'] ?? '';
		return self::parse_suggestions( $text );
	}

	private static function parse_suggestions( string $text ): array {
		/* Strip markdown code fences if present */
		$text = preg_replace( '/^```(?:json)?\s*/m', '', $text );
		$text = preg_replace( '/```\s*$/m', '', $text );
		$text = trim( $text );

		/* Find JSON array in text */
		if ( preg_match( '/\[.*\]/s', $text, $m ) ) {
			$arr = json_decode( $m[0], true );
			if ( is_array( $arr ) && count( $arr ) > 0 ) {
				return [ 'suggestions' => array_values( $arr ) ];
			}
		}

		return [ 'error' => 'AI yanıtı ayrıştırılamadı. Lütfen tekrar deneyin.' ];
	}
}
