import { Controller, Get, Post, Body, Patch, Param, Delete, Query, UseGuards, BadRequestException, UseInterceptors, UploadedFile } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth, ApiConsumes, ApiBody } from '@nestjs/swagger';
import { FileInterceptor } from '@nestjs/platform-express';
import { ItemsService } from './items.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { validateItemPhoto, saveItemPhoto } from './photo-upload.helper';

@ApiTags('Items')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('items')
export class ItemsController {
  constructor(private readonly itemsService: ItemsService) {}

  @Post('upload-photo')
  @UseInterceptors(FileInterceptor('file', { limits: { fileSize: 5 * 1024 * 1024 } }))
  @ApiOperation({ summary: 'Upload item photo (before creating item)' })
  @ApiConsumes('multipart/form-data')
  @ApiBody({ schema: { type: 'object', properties: { file: { type: 'string', format: 'binary' } } } })
  async uploadPhoto(@UploadedFile() file: Express.Multer.File) {
    if (!file) {
      throw new BadRequestException('Dosya seçilmedi');
    }
    validateItemPhoto(file);
    const url = await saveItemPhoto(file);
    return { url };
  }

  @Post()
  @ApiOperation({ summary: 'Create a new item' })
  async create(@Body() createItemDto: any) {
    return this.itemsService.create(createItemDto);
  }

  @Get()
  @ApiOperation({ summary: 'Get all items' })
  async findAll(@Query('contractId') contractId?: string) {
    return this.itemsService.findAll(contractId);
  }

  @Get('customer/:customerId')
  @ApiOperation({ summary: 'Get items by customer ID' })
  async findByCustomerId(@Param('customerId') customerId: string) {
    return this.itemsService.findByCustomerId(customerId);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get item by ID' })
  async findOne(@Param('id') id: string) {
    return this.itemsService.findOne(id);
  }

  @Patch(':id')
  @ApiOperation({ summary: 'Update item' })
  async update(@Param('id') id: string, @Body() updateItemDto: any) {
    return this.itemsService.update(id, updateItemDto);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'Remove item (soft delete)' })
  async remove(@Param('id') id: string) {
    await this.itemsService.remove(id);
    return { message: 'Item removed successfully' };
  }
}
