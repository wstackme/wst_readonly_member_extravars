<?php
// Copyright 2020 Webstack. All rights reserved.
// 본 소프트웨어는 웹스택이 개발/운용하는 웹스택의 재산으로, 허가없이 무단 이용할 수 없습니다.
// 무단 복제 또는 배포 등의 행위는 관련 법규에 의하여 금지되어 있으며, 위반시 민/형사상의 처벌을 받을 수 있습니다.
// 관련 문의는 웹사이트<https://webstack.me/> 또는 이메일<admin@webstack.me> 로 부탁드립니다.

if(!defined('__XE__'))
{
	exit();
}

// 라이브러리 로드
require_once(__DIR__ . '/functions.php');

// 애드온 설정 기본값 지정
$addon_info = AddonFunction::setDefaultAddonInfo($addon_info, [
	'use_hide' => [ 'type' => 'YN', 'default' => 'Y' ],
	'closest_hider' => 'li'
]);

// 회원정보 사용자정의 항목 설정 페이지 수정
if($called_position == 'after_module_proc')
{
	if($this->act == 'getMemberAdminInsertJoinForm')
	{
		// 사용자정의 정보 읽어오기
		$join_srl = Context::get('member_join_form_srl');
		$join_info = AddonFunction::getCache($join_srl);
		$is_readonly = $join_info->readonly == 'Y';

		// 삽입할 템플릿 생성
		$tpl_adding = '<div class="x_control-group">
			<label class="x_control-label">읽기 전용</label>
			<div class="x_controls">
				<label for="radio_editable" class="x_inline"><input type="radio" id="radio_editable" name="readonly" value="N"' . (!$is_readonly ? ' checked="checked"' : '') . '> 수정가능</label>
				<label for="radio_readonly" class="x_inline"><input type="radio" id="radio_readonly" name="readonly" value="Y"' . ($is_readonly ? ' checked="checked"' : '') . '> 읽기전용</label>
			</div>
		</div>';

		// 템플릿 교체
		$tpl = $this->get('tpl');
		$tpl = str_replace("</div> \t<div class=\"x_modal-footer\"", $tpl_adding . "</div> \t<div class=\"x_modal-footer\"", $tpl);
		$this->add('tpl', $tpl);
	}
}

// 회원정보 사용자정의 항목 설정 페이지 제출
if($called_position == 'after_module_proc')
{
	if($this->act == 'procMemberAdminInsertJoinForm')
	{
		// member_join_form_srl 확인 - 존재하지 않을 경우 생성된 srl 을 추척
		$join_srl = Context::get('member_join_form_srl');
		if(!$join_srl)
		{
			$join_srl = end($_SESSION['seq']);
		}

		// 사용자정의 정보 입력
		$join_info = new stdClass();
		$join_info->name = Context::get('column_id');
		$join_info->readonly = Context::get('readonly');
		AddonFunction::setCache($join_srl, $join_info, -1);
	}
}

// 회원정보 사용자정의 항목 삭제 시
if($called_position == 'after_module_proc')
{
	if($this->act == 'procMemberAdminDeleteJoinForm')
	{
		$join_srl = Context::get('member_join_form_srl');
		AddonFunction::deleteCache($join_srl);
	}
}

// 회원정보수정 페이지에서 수정 방지 - 뷰
if($called_position == 'after_module_proc')
{
	if($this->act == 'dispMemberModifyInfo' || $this->act == 'dispMemberSignUpForm')
	{
		// 사용자정의 목록 불러오기
		$readonly_list = scandir('./files/webstack/addons/wst_readonly_member_extravars/');

		// 폼태그 불러오기
		$formTags = Context::get('formTags');
		foreach($formTags as &$formTag)
		{
			// 사용자정의 반복
			foreach($readonly_list as $join_srl)
			{
				if(is_dir($join_srl))
				{
					continue;
				}

				// 사용자정의 정보 읽어오기
				$join_info = AddonFunction::getCache(str_replace('.php', '', $join_srl));
				if($join_info->name != $formTag->name)
				{
					continue;
				}

				// 읽기전용인 경우
				if($join_info->readonly == 'Y')
				{
					$formTag->inputTag = str_replace('<input', '<input disabled="disabled"', $formTag->inputTag);
					$formTag->inputTag = str_replace('<textarea', '<textarea disabled="disabled"', $formTag->inputTag);
					$formTag->inputTag = str_replace('<select', '<select disabled="disabled"', $formTag->inputTag);

					// 숨김 설정시
					if($addon_info->use_hide)
					{
						$formTag->inputTag = '<span class="wst_readonly_menu_extravars_hider"></span>';
					}
				}
			}
		}

		// 수정된 폼태그 지정
		Context::get('formTags', $formTags);
	}
}

// 회원정보수정 페이지에서 수정 방지 - 컨트롤러
if($called_position == 'before_module_init')
{
	if(Context::get('act') == 'procMemberModifyInfo')
	{
		// 로그인 정보 불러오기
		$logged_info = Context::get('logged_info');

		// 사용자정의 목록 불러오기
		$readonly_list = scandir('./files/webstack/addons/wst_readonly_member_extravars/');

		// 사용자정의 반복
		foreach($readonly_list as $join_srl)
		{
			if(is_dir($join_srl))
			{
				continue;
			}

			// 사용자정의 정보 읽어오기
			$join_info = AddonFunction::getCache(str_replace('.php', '', $join_srl));

			// 제출값의 읽기전용 속성 고정하기
			$_POST[$join_info->name] = $logged_info->{$join_info->name};
			Context::set($join_info->name, $logged_info->{$join_info->name});
		}
	}
}